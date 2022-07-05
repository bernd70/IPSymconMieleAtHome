<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';
require_once __DIR__ . '/../libs/images.php';

class MieleAtHomeConfig extends IPSModule
{
    use MieleAtHome\StubsCommonLib;
    use MieleAtHomeLocalLib;
    use MieleAtHomeImagesLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{996743FB-1712-47A3-9174-858A08A13523}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $propertyNames = ['ImportCategoryID'];
        $this->MaintainReferences($propertyNames);

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function getConfiguratorValues()
    {
        $entries = [];

        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return $entries;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            return $entries;
        }

        $catID = $this->ReadPropertyInteger('ImportCategoryID');

        $SendData = ['DataID' => '{AE164AF6-A49F-41BD-94F3-B4829AAA0B55}', 'Function' => 'GetDevices'];
        $data = $this->SendDataToParent(json_encode($SendData));
        $devices = json_decode($data, true);

        $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);

        $guid = '{C2672DE6-E854-40C0-86E0-DE1B6B4C3CAB}'; // Miele@Home Device
        $instIDs = IPS_GetInstanceListByModuleID($guid);

        if (is_array($devices)) {
            foreach ($devices as $fabNumber => $device) {
                $this->SendDebug(__FUNCTION__, 'fabNumber=' . $fabNumber . ', device=' . json_encode($device), 0);

                $instanceID = 0;
                foreach ($instIDs as $instID) {
                    if ($fabNumber == IPS_GetProperty($instID, 'fabNumber')) {
                        $MieleatHome_device_name = IPS_GetName($instID);
                        $this->SendDebug(__FUNCTION__, 'device found: ' . utf8_decode($MieleatHome_device_name) . ' (' . $instID . ')', 0);
                        $instanceID = $instID;
                        break;
                    }
                }

                if ($instanceID == 0) {
                    $SendData = ['DataID' => '{AE164AF6-A49F-41BD-94F3-B4829AAA0B55}', 'Function' => 'GetDeviceIdent', 'Ident' => $fabNumber];
                    $device_data = $this->SendDataToParent(json_encode($SendData));
                    $this->SendDebug(__FUNCTION__, 'device_data=' . $device_data, 0);

                    $device = json_decode($device_data, true);
                    $deviceId = $device['type']['value_raw'];
                    $deviceType = $device['type']['value_localized'];
                    $techType = $device['deviceIdentLabel']['techType'];
                    $deviceName = $device['deviceName'];
                    if ($deviceName == '') {
                        $deviceName = $deviceType;
                    }
                } else {
                    $deviceId = IPS_GetProperty($instID, 'deviceId');
                    $deviceType = IPS_GetProperty($instID, 'deviceType');
                    $techType = IPS_GetProperty($instID, 'techType');
                    $fabNumber = IPS_GetProperty($instID, 'fabNumber');
                    $deviceName = IPS_GetName($instID);
                }

                $entry = [
                    'instanceID'  => $instanceID,
                    'id'          => $deviceId,
                    'name'        => $deviceName,
                    'tech_type'   => $techType,
                    'device_type' => $deviceType,
                    'fabNumber'   => $fabNumber,
                    'create'      => [
                        'moduleID'      => $guid,
                        'location'      => $this->GetConfiguratorLocation($catID),
                        'info'          => $deviceType . ' (' . $techType . ')',
                        'configuration' => [
                            'deviceId'   => $deviceId,
                            'deviceType' => $deviceType,
                            'fabNumber'  => $fabNumber,
                            'techType'   => $techType
                        ]
                    ]
                ];

                $entries[] = $entry;
            }
        }

        foreach ($instIDs as $instID) {
            $fnd = false;
            foreach ($entries as $entry) {
                if ($entry['instanceID'] == $instID) {
                    $fnd = true;
                    break;
                }
            }
            if ($fnd) {
                continue;
            }

            $deviceName = IPS_GetName($instID);
            $deviceId = IPS_GetProperty($instID, 'deviceId');
            $deviceType = IPS_GetProperty($instID, 'deviceType');
            $techType = IPS_GetProperty($instID, 'techType');
            $fabNumber = IPS_GetProperty($instID, 'fabNumber');

            $entry = [
                'instanceID'  => $instID,
                'id'          => $deviceId,
                'name'        => $deviceName,
                'tech_type'   => $techType,
                'device_type' => $deviceType,
                'fabNumber'   => $fabNumber,
            ];
            $entries[] = $entry;
            $this->SendDebug(__FUNCTION__, 'missing entry=' . print_r($entry, true), 0);
        }

        return $entries;
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Miele@Home configurator');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'SelectCategory',
            'name'    => 'ImportCategoryID',
            'caption' => 'category for Miele@Home devices to be created'
        ];

        $entries = $this->getConfiguratorValues();
        $formElements[] = [
            'name'     => 'MieleatHomeConfiguration',
            'type'     => 'Configurator',
            'rowCount' => count($entries),
            'add'      => false,
            'delete'   => false,
            'sort'     => [
                'column'    => 'name',
                'direction' => 'ascending'
            ],
            'columns' => [
                [
                    'caption' => 'ID',
                    'name'    => 'id',
                    'width'   => '200px',
                    'visible' => false
                ],
                [
                    'caption' => 'device name',
                    'name'    => 'name',
                    'width'   => 'auto'
                ],
                [
                    'caption' => 'Model',
                    'name'    => 'tech_type',
                    'width'   => '250px'
                ],
                [
                    'caption' => 'Label',
                    'name'    => 'device_type',
                    'width'   => '300px'
                ],
                [
                    'caption' => 'Fabrication number',
                    'name'    => 'fabNumber',
                    'width'   => '200px'
                ]
            ],
            'values' => $entries
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }
}
