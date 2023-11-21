<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';
require_once __DIR__ . '/../libs/images.php';

class MieleAtHomeDevice extends IPSModule
{
    use MieleAtHome\StubsCommonLib;
    use MieleAtHomeLocalLib;
    use MieleAtHomeImagesLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyBoolean('log_no_parent', true);

        $this->RegisterPropertyInteger('deviceId', 0);
        $this->RegisterPropertyString('deviceType', '');
        $this->RegisterPropertyString('fabNumber', '');
        $this->RegisterPropertyString('techType', '');

        $this->RegisterPropertyInteger('update_interval', 60);

        $this->RegisterPropertyBoolean('map_programName', false);
        $this->RegisterPropertyBoolean('map_programType', true);
        $this->RegisterPropertyBoolean('map_programPhase', false);
        $this->RegisterPropertyBoolean('map_dryingStep', true);
        $this->RegisterPropertyBoolean('map_ventilationStep', true);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{996743FB-1712-47A3-9174-858A08A13523}');

        $this->RegisterTimer('UpdateData', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function getDeviceOptions($deviceId)
    {
        $opts = [
            'program_name'          => false,
            'program_type'          => false,
            'program_phase'         => false,
            'times'                 => false,
            'wash_temp'             => false,
            'spinning_speed'        => false,
            'drying_step'           => false,
            'ventilation_step'      => false,
            'oven_temp'             => false,
            'fridge_temp'           => false,
            'freezer_temp'          => false,
            'door'                  => false,
            'ecoFeedback_Water'     => false,
            'ecoFeedback_Energy'    => false,
            'batteryLevel'          => false,
            'fridge_zone'           => 0,
            'freezer_zone'          => 0,
            'enabled_action'        => false,
            'enabled_starttime'     => false,
            'enabled_superfreezing' => false,
            'enabled_supercooling'  => false,
            'enabled_light'         => false,
            'enabled_powersupply'   => false,
            'enabled_fridge_temp'   => false,
            'enabled_freezer_temp'  => false,
            'core_temp'             => false,
            'enabled_operationmode' => false,
        ];

        switch ($deviceId) {
            case self::$DEVICE_WASHING_MACHINE:   					// Waschmaschine
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['wash_temp'] = true;
                $opts['spinning_speed'] = true;
                $opts['door'] = true;
                $opts['ecoFeedback_Water'] = true;
                $opts['ecoFeedback_Energy'] = true;

                $opts['enabled_powersupply'] = true;
                $opts['enabled_action'] = true;
                $opts['enabled_starttime'] = true;
                break;
            case self::$DEVICE_TUMBLE_DRYER:      					// Trockner
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['drying_step'] = true;
                $opts['door'] = true;
                $opts['ecoFeedback_Energy'] = true;

                $opts['enabled_powersupply'] = true;
                $opts['enabled_action'] = true;
                $opts['enabled_starttime'] = true;
                break;
            case self::$DEVICE_DISHWASHER:         					// Geschirrspüler
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['door'] = true;
                $opts['ecoFeedback_Water'] = true;
                $opts['ecoFeedback_Energy'] = true;

                $opts['enabled_powersupply'] = true;
                $opts['enabled_action'] = true;
                $opts['enabled_starttime'] = true;
                break;
            case self::$DEVICE_OVEN:               					// Backofen
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['oven_temp'] = true;
                $opts['core_temp'] = true;
                $opts['door'] = true;

                $opts['enabled_powersupply'] = true;
                break;
            case self::$DEVICE_OVEN_MICROWAVE:     					// Backofen mit Mikrowelle
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['oven_temp'] = true;
                $opts['door'] = true;

                $opts['enabled_powersupply'] = true;
                break;
            case self::$DEVICE_FRIDGE:								// Kühlschrank
                $opts['fridge_temp'] = true;
                $opts['fridge_zone'] = 1;
                $opts['door'] = true;

                $opts['enabled_operationmode'] = true;
                $opts['enabled_supercooling'] = true;
                $opts['enabled_fridge_temp'] = true;
                break;
            case self::$DEVICE_FREEZER:								// Gefrierschrank
                $opts['freezer_temp'] = true;
                $opts['freezer_zone'] = 1;
                $opts['door'] = true;

                $opts['enabled_operationmode'] = true;
                $opts['enabled_superfreezing'] = true;
                $opts['enabled_freezer_temp'] = true;
                break;
            case self::$DEVICE_FRIDGE_FREEZER:						// Kühl-/Gefrierkombination
                $opts['fridge_temp'] = true;
                $opts['fridge_zone'] = 1;
                $opts['freezer_temp'] = true;
                $opts['freezer_zone'] = 2;
                $opts['door'] = true;

                $opts['enabled_operationmode'] = true;
                $opts['enabled_supercooling'] = true;
                $opts['enabled_superfreezing'] = true;
                $opts['enabled_fridge_temp'] = true;
                $opts['enabled_freezer_temp'] = true;
                break;
            case self::$DEVICE_DISH_WARMER:							// Wärmeschublade
                $opts['program_name'] = true;
                break;
            case self::$DEVICE_STEAM_OVEN_COMBINATION: 				// Dampfgarer mit Backofen-Funktion
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['oven_temp'] = true;
                $opts['core_temp'] = true;
                $opts['door'] = true;

                $opts['enabled_powersupply'] = true;
                break;
            case self::$DEVICE_STEAM_OVEN_MICROWAVE_COMBINATION:	// Dampfgarer mit Mikrowelle
                $opts['program_name'] = true;
                $opts['program_type'] = true;
                $opts['program_phase'] = true;
                $opts['times'] = true;
                $opts['oven_temp'] = true;
                $opts['door'] = true;

                $opts['enabled_powersupply'] = true;
                break;
        }
        return $opts;
    }

    public function MessageSink($tstamp, $senderID, $message, $data)
    {
        parent::MessageSink($tstamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $r = [];

        if ($this->version2num($oldInfo) < $this->version2num('1.30')) {
            $r[] = $this->Translate('Adjust variableprofile \'MieleAtHome.Status\'');
        }

        if ($this->version2num($oldInfo) < $this->version2num('2.0')) {
            $r[] = $this->Translate('Changing the polling interval to hourly (due to the use of server sent events (SSE))');
        }

        if ($this->version2num($oldInfo) < $this->version2num('2.0.5')) {
            $deviceId = $this->ReadPropertyInteger('deviceId');
            $opts = $this->getDeviceOptions($deviceId);

            if ($opts['enabled_powersupply'] == false) {
                @$varID = $this->GetIDForIdent('PowerSupply');
                if (@$varID != false) {
                    $r[] = $this->Translate('Delete variable \'PowerSupply\'');
                }
            }

            if ($opts['enabled_operationmode']) {
                @$varID = $this->GetIDForIdent('OperationMode');
                if (@$varID != false) {
                    $r[] = $this->Translate('Delete variable \'OperationMode\'');
                }
            }
        }

        return $r;
    }

    private function CompleteModuleUpdate(array $oldInfo, array $newInfo)
    {
        if ($this->version2num($oldInfo) < $this->version2num('1.30')) {
            if (IPS_VariableProfileExists('MieleAtHome.Status')) {
                IPS_DeleteVariableProfile('MieleAtHome.Status');
            }
            $this->InstallVarProfiles(false);
        }

        if ($this->version2num($oldInfo) < $this->version2num('2.0')) {
            IPS_SetProperty($this->InstanceID, 'update_interval', 60);
        }

        if ($this->version2num($oldInfo) < $this->version2num('2.0.5')) {
            $deviceId = $this->ReadPropertyInteger('deviceId');
            $opts = $this->getDeviceOptions($deviceId);

            if ($opts['enabled_powersupply'] == false) {
                @$varID = $this->GetIDForIdent('PowerSupply');
                if (@$varID != false) {
                    $this->UnregisterVariable('PowerSupply');
                }
            }

            if ($opts['enabled_operationmode']) {
                @$varID = $this->GetIDForIdent('OperationMode');
                if (@$varID != false) {
                    $this->UnregisterVariable('OperationMode');
                }
            }

            if (IPS_VariableProfileExists('MieleAtHome.OperationMode')) {
                IPS_DeleteVariableProfile('MieleAtHome.OperationMode');
            }
        }

        return '';
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $deviceId = $this->ReadPropertyInteger('deviceId');
        if ($deviceId == 0) {
            $this->SendDebug(__FUNCTION__, '"deviceId" is needed', 0);
            $r[] = $this->Translate('Device id must be specified');
        }
        $fabNumber = $this->ReadPropertyString('fabNumber');
        if ($fabNumber == '') {
            $this->SendDebug(__FUNCTION__, '"fabNumber" is needed', 0);
            $r[] = $this->Translate('Fabrication number must be specified');
        }

        return $r;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $deviceId = $this->ReadPropertyInteger('deviceId');
        $opts = $this->getDeviceOptions($deviceId);
        $this->SendDebug(__FUNCTION__, 'options=' . print_r($opts, true), 0);

        $vpos = 1;

        $this->MaintainVariable('State', $this->Translate('State'), VARIABLETYPE_INTEGER, 'MieleAtHome.Status', $vpos++, true);
        $this->MaintainVariable('Info', $this->Translate('Information available'), VARIABLETYPE_BOOLEAN, 'MieleAtHome.YesNo', $vpos++, true);
        $this->MaintainVariable('Failure', $this->Translate('Failure detected'), VARIABLETYPE_BOOLEAN, 'MieleAtHome.YesNo', $vpos++, true);

        $this->MaintainVariable('PowerSupply', $this->Translate('Power supply'), VARIABLETYPE_INTEGER, 'MieleAtHome.PowerSupply', $vpos++, $opts['enabled_powersupply']);

        $this->MaintainVariable('Action', $this->Translate('Action'), VARIABLETYPE_INTEGER, 'MieleAtHome.Action', $vpos++, $opts['enabled_action']);
        $this->MaintainVariable('Superfreezing', $this->Translate('Superfreezing'), VARIABLETYPE_INTEGER, 'MieleAtHome.Superfreezing', $vpos++, $opts['enabled_superfreezing']);
        $this->MaintainVariable('Supercooling', $this->Translate('Supercooling'), VARIABLETYPE_INTEGER, 'MieleAtHome.Supercooling', $vpos++, $opts['enabled_supercooling']);

        $this->MaintainVariable('ProgramName', $this->Translate('Program name'), VARIABLETYPE_STRING, '', $vpos++, $opts['program_name']);
        $this->MaintainVariable('ProgramType', $this->Translate('Program'), VARIABLETYPE_STRING, '', $vpos++, $opts['program_type']);

        $this->MaintainVariable('ProgramPhase', $this->Translate('Phase'), VARIABLETYPE_STRING, '', $vpos++, $opts['program_phase']);

        $this->MaintainVariable('StartTime', $this->Translate('Start at'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $opts['times']);
        $this->MaintainVariable('ElapsedTime', $this->Translate('Elapsed time'), VARIABLETYPE_INTEGER, 'MieleAtHome.Duration', $vpos++, $opts['times']);
        $this->MaintainVariable('RemainingTime', $this->Translate('Remaining time'), VARIABLETYPE_INTEGER, 'MieleAtHome.Duration', $vpos++, $opts['times']);
        $this->MaintainVariable('EndTime', $this->Translate('End at'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $opts['times']);
        $this->MaintainVariable('WorkProgress', $this->Translate('Work progress'), VARIABLETYPE_INTEGER, 'MieleAtHome.WorkProgress', $vpos++, $opts['times']);

        $this->MaintainVariable('Wash_TargetTemperature', $this->Translate('Temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['wash_temp']);

        $this->MaintainVariable('SpinningSpeed', $this->Translate('Spinning speed'), VARIABLETYPE_INTEGER, 'MieleAtHome.SpinningSpeed', $vpos++, $opts['spinning_speed']);

        $this->MaintainVariable('DryingStep', $this->Translate('Drying step'), VARIABLETYPE_STRING, '', $vpos++, $opts['drying_step']);

        $this->MaintainVariable('VentilationStep', $this->Translate('Ventilation step'), VARIABLETYPE_STRING, '', $vpos++, $opts['ventilation_step']);

        $this->MaintainVariable('Fridge_TargetTemperature', $this->Translate('Fridge: target temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['fridge_temp']);
        $this->MaintainVariable('Fridge_Temperature', $this->Translate('Fridge: temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['fridge_temp']);

        $this->MaintainVariable('Freezer_TargetTemperature', $this->Translate('Freezer: target temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['freezer_temp']);
        $this->MaintainVariable('Freezer_Temperature', $this->Translate('Freezer: temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['freezer_temp']);

        $this->MaintainVariable('Door', $this->Translate('Door'), VARIABLETYPE_BOOLEAN, 'MieleAtHome.Door', $vpos++, $opts['door']);

        $this->MaintainVariable('Light', $this->Translate('Light'), VARIABLETYPE_INTEGER, 'MieleAtHome.Light', $vpos++, $opts['enabled_light']);

        $this->MaintainVariable('Oven_TargetTemperature', $this->Translate('Target temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['oven_temp']);
        $this->MaintainVariable('Oven_Temperature', $this->Translate('Temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['oven_temp']);

        $this->MaintainVariable('Core_TargetTemperature', $this->Translate('Target core temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['core_temp']);
        $this->MaintainVariable('Core_Temperature', $this->Translate('Core temperature'), VARIABLETYPE_INTEGER, 'MieleAtHome.Temperature', $vpos++, $opts['core_temp']);

        $this->MaintainVariable('OperationMode_Sabbath', $this->Translate('Sabbath mode'), VARIABLETYPE_BOOLEAN, '', $vpos++, $opts['enabled_operationmode']);
        $this->MaintainVariable('OperationMode_Party', $this->Translate('Party mode'), VARIABLETYPE_BOOLEAN, '', $vpos++, $opts['enabled_operationmode']);
        $this->MaintainVariable('OperationMode_Holiday', $this->Translate('Holiday mode'), VARIABLETYPE_BOOLEAN, '', $vpos++, $opts['enabled_operationmode']);

        $vpos = 80;
        $this->MaintainVariable('CurrentWaterConsumption', $this->Translate('Current water consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Water', $vpos++, $opts['ecoFeedback_Water']);
        $this->MaintainVariable('EstimatedWaterConsumption', $this->Translate('Estimated water consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Water', $vpos++, $opts['ecoFeedback_Water']);
        $this->MaintainVariable('LastWaterConsumption', $this->Translate('Last water consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Water', $vpos++, $opts['ecoFeedback_Water']);
        $this->MaintainVariable('CurrentEnergyConsumption', $this->Translate('Current energy consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Energy', $vpos++, $opts['ecoFeedback_Energy']);
        $this->MaintainVariable('EstimatedEnergyConsumption', $this->Translate('Estimated energy consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Energy', $vpos++, $opts['ecoFeedback_Energy']);
        $this->MaintainVariable('LastEnergyConsumption', $this->Translate('Last energy consumption'), VARIABLETYPE_FLOAT, 'MieleAtHome.Energy', $vpos++, $opts['ecoFeedback_Energy']);

        $vpos = 90;
        $this->MaintainVariable('BatteryLevel', $this->Translate('Battery level'), VARIABLETYPE_INTEGER, 'MieleAtHome.BatteryLevel', $vpos++, $opts['batteryLevel']);

        $vpos = 100;
        $this->MaintainVariable('LastChange', $this->Translate('last change'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $techType = $this->ReadPropertyString('techType');
        $fabNumber = $this->ReadPropertyString('fabNumber');
        $this->SetSummary($techType . ' (#' . $fabNumber . ')');

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        if ($opts['enabled_action']) {
            $this->MaintainAction('Action', true);
        }
        if ($opts['enabled_starttime']) {
            $this->MaintainAction('StartTime', true);
        }
        if ($opts['enabled_superfreezing']) {
            $this->MaintainAction('Superfreezing', true);
        }
        if ($opts['enabled_supercooling']) {
            $this->MaintainAction('Supercooling', true);
        }
        if ($opts['enabled_light']) {
            $this->MaintainAction('Light', true);
        }
        if ($opts['enabled_powersupply']) {
            $this->MaintainAction('PowerSupply', true);
        }
        if ($opts['enabled_fridge_temp']) {
            $this->MaintainAction('Fridge_TargetTemperature', true);
        }
        if ($opts['enabled_freezer_temp']) {
            $this->MaintainAction('Freezer_TargetTemperature', true);
        }

        if ($opts['enabled_operationmode']) {
            $this->MaintainAction('OperationMode_Sabbath', true);
            $this->MaintainAction('OperationMode_Party', true);
            $this->MaintainAction('OperationMode_Holiday', true);
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Miele@Home device');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'NumberSpinner',
                    'enabled' => false,
                    'name'    => 'deviceId',
                    'caption' => 'Device id'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'enabled' => false,
                    'name'    => 'deviceType',
                    'caption' => 'Device type'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'enabled' => false,
                    'name'    => 'fabNumber',
                    'caption' => 'Fabrication number'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'enabled' => false,
                    'name'    => 'techType',
                    'caption' => 'Model'
                ],
            ],
            'caption' => 'Basic configuration (don\'t change)'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'mapping code to text of field ...'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'map_programName',
                    'caption' => ' ... Program name'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'map_programType',
                    'caption' => ' ... Program'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'map_programPhase',
                    'caption' => ' ... Phase'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'map_dryingStep',
                    'caption' => ' ... Drying step'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'map_ventilationStep',
                    'caption' => ' ... Ventilation step'
                ],
            ],
            'caption' => 'Settings'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'NumberSpinner',
                    'minimum' => 0,
                    'suffix'  => 'Minutes',
                    'name'    => 'update_interval',
                    'caption' => 'Update interval'
                ],
            ],
            'caption' => 'Communication'
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'log_no_parent',
            'caption' => 'Generate message when the gateway is inactive',
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

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ]
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Test area',
            'expanded'  => false,
            'items'     => [
                [
                    'type'    => 'TestCenter',
                ],
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 60 * 1000 : 0;
        $this->MaintainTimer('UpdateData', $msec);
    }

    public function ReceiveData($data)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);
        $event = $this->GetArrayElem($jdata, 'Event', 0);
        $data = $this->GetArrayElem($jdata, 'Data', 0);
        $jdata = json_decode($data, true);
        $fabNumber = $this->ReadPropertyString('fabNumber');
        if (isset($jdata[$fabNumber])) {
            $this->SendDebug(__FUNCTION__, 'event=' . $event . '=' . print_r($jdata[$fabNumber], true), 0);
            if ($event == 'devices' && isset($jdata[$fabNumber]['state'])) {
                $this->DecodeDevice('Event', $jdata[$fabNumber]['state']);
            }
            if ($event == 'actions') {
                $this->DecodeActions('Event', $jdata[$fabNumber]);
            }
        }
    }

    private function DecodeDevice($source, $jdata)
    {
        $this->SendDebug(__FUNCTION__, 'source=' . $source . ', jdata=' . print_r($jdata, true), 0);

        $map_programName = $this->ReadPropertyBoolean('map_programName');
        $map_programType = $this->ReadPropertyBoolean('map_programType');
        $map_programPhase = $this->ReadPropertyBoolean('map_programPhase');
        $map_dryingStep = $this->ReadPropertyBoolean('map_dryingStep');
        $map_ventilationStep = $this->ReadPropertyBoolean('map_ventilationStep');

        $deviceId = $this->ReadPropertyInteger('deviceId');
        $opts = $this->getDeviceOptions($deviceId);

        $now = time();

        $is_changed = false;
        $fnd = false;

        $value_raw = $this->GetArrayElem($jdata, 'status.value_raw', 0, $fnd);
        if ($fnd) {
            $r = IPS_GetVariableProfile('MieleAtHome.Status');
            $status = self::$STATE_UNKNOWN;
            foreach ($r['Associations'] as $a) {
                if ($a['Value'] == $value_raw) {
                    $status = $value_raw;
                    break;
                }
            }
            $this->SendDebug(__FUNCTION__, 'set "State" to ' . $status, 0);
            $this->SaveValue('State', $status, $is_changed);
            if ($status == self::$STATE_UNKNOWN) {
                $e = 'unknown value ' . $value_raw;
                $value_localized = $this->GetArrayElem($jdata, 'status.value_localized', '');
                if ($value_localized != '') {
                    $e .= ' (' . $value_localized . ')';
                }
                $this->SendDebug(__FUNCTION__, $e, 0);
                $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
            }
        } else {
            $status = $this->GetValue('State');
        }

        $signalFailure = (bool) $this->GetArrayElem($jdata, 'signalFailure', false, $fnd);
        if ($fnd) {
            $this->SendDebug(__FUNCTION__, 'set "Failure" to ' . $this->bool2str($signalFailure), 0);
            $this->SaveValue('Failure', $signalFailure, $is_changed);
        }

        $signalInfo = (bool) $this->GetArrayElem($jdata, 'signalInfo', false, $fnd);
        if ($fnd) {
            $this->SendDebug(__FUNCTION__, 'set "Info" to ' . $this->bool2str($signalInfo), 0);
            $this->SaveValue('Info', $signalInfo, $is_changed);
        }

        $base_ts = strtotime(date('d.m.Y H:i:00', $now));

        if ($opts['program_type']) {
            $programType = '';
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                if ($map_programType) {
                    $value_raw = $this->GetArrayElem($jdata, 'programType.value_raw', 0, $fnd);
                    if ($fnd) {
                        $programType = $this->programType2text($deviceId, $value_raw);
                    }
                } else {
                    $programType = $this->GetArrayElem($jdata, 'programType.value_localized', '', $fnd);
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "ProgramType" to "' . $programType . '"', 0);
                $this->SaveValue('ProgramType', $programType, $is_changed);
            }
        }
        if ($opts['program_name']) {
            $programName = '';
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                if ($map_programName) {
                    $value_raw = $this->GetArrayElem($jdata, 'programID.value_raw', 0, $fnd);
                    if ($fnd) {
                        $programName = $this->programId2text($deviceId, $value_raw);
                    }
                } else {
                    $programName = $this->GetArrayElem($jdata, 'ProgramID.value_localized', '', $fnd);
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "ProgramName" to "' . $programName . '"', 0);
                $this->SaveValue('ProgramName', $programName, $is_changed);
            }
        }

        if ($opts['program_phase']) {
            $programPhase = '';
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                if ($map_programPhase) {
                    $value_raw = $this->GetArrayElem($jdata, 'programPhase.value_raw', 0, $fnd);
                    if ($fnd && $value_raw != 65535) {
                        $programPhase = $this->programPhase2text($deviceId, $value_raw);
                    }
                } else {
                    $programPhase = $this->GetArrayElem($jdata, 'programPhase.value_localized', '', $fnd);
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "ProgramPhase" to "' . $programPhase . '"', 0);
                $this->SaveValue('ProgramPhase', $programPhase, $is_changed);
            }
        }

        if ($opts['times']) {
            $remainingTime = 0;
            $elapsedTime = 0;
            $startTime = 0;
            $endTime = 0;
            $workProgress = 0;
            if ($status != self::$STATE_OFF) {
                $remainingTime_H = $this->GetArrayElem($jdata, 'remainingTime.0', 0);
                $remainingTime_M = $this->GetArrayElem($jdata, 'remainingTime.1', 0);
                $remainingTime = $remainingTime_H * 60 + $remainingTime_M;

                if ($status == self::$STATE_WAITING_TO_START) {
                    $startTime_H = $this->GetArrayElem($jdata, 'startTime.0', 0);
                    $startTime_M = $this->GetArrayElem($jdata, 'startTime.1', 0);
                    $startDelay = ($startTime_H * 60 + $startTime_M) * 60;

                    if ($startDelay > 0) {
                        $startTime = $base_ts + $startDelay;
                    }
                    if ($remainingTime > 0) {
                        $endTime = $startTime + $remainingTime * 60;
                    }
                    $elapsedTime = 0;
                } elseif ($status != self::$STATE_ON) {
                    $elapsedTime_H = $this->GetArrayElem($jdata, 'elapsedTime.0', 0);
                    $elapsedTime_M = $this->GetArrayElem($jdata, 'elapsedTime.1', 0);
                    $elapsedTime = $elapsedTime_H * 60 + $elapsedTime_M;

                    $startTime = $base_ts - $elapsedTime * 60;
                    if ($remainingTime > 0) {
                        $endTime = $base_ts + $remainingTime * 60;
                    }

                    if ($elapsedTime && $remainingTime) {
                        $workProgress = floor($elapsedTime / ($elapsedTime + $remainingTime) * 100);
                        $this->SendDebug(__FUNCTION__, 'elapsedTime=' . $elapsedTime . ', remainingTime=' . $remainingTime . ' => workProgress=' . $workProgress, 0);
                    } else {
                        $workProgress = 100;
                    }
                }
            }
            $this->SendDebug(__FUNCTION__, 'set "RemainingTime" to ' . $remainingTime, 0);
            $this->SaveValue('RemainingTime', $remainingTime, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set "ElapsedTime" to ' . $elapsedTime, 0);
            $this->SaveValue('ElapsedTime', $elapsedTime, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set "StartTime" to ' . $startTime, 0);
            $this->SaveValue('StartTime', $startTime, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set "EndTime" to ' . $endTime, 0);
            $this->SaveValue('EndTime', $endTime, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set "WorkProgress" to ' . $workProgress, 0);
            $this->SaveValue('WorkProgress', $workProgress, $is_changed);
        }

        if ($opts['wash_temp']) {
            $targetTemperature = 0;
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                $targetTemperature = $this->GetArrayElem($jdata, 'targetTemperature.0.value_localized', 0, $fnd);
                if ($targetTemperature <= -326) {
                    $targetTemperature = 0;
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "Wash_TargetTemperature" to ' . $targetTemperature, 0);
                $this->SaveValue('Wash_TargetTemperature', $targetTemperature, $is_changed);
            }
        }

        if ($opts['spinning_speed']) {
            $spinningSpeed = 0;
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                $spinningSpeed = $this->GetArrayElem($jdata, 'spinningSpeed.value_raw', 0, $fnd);
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "SpinningSpeed" to ' . $spinningSpeed, 0);
                $this->SaveValue('SpinningSpeed', $spinningSpeed, $is_changed);
            }
        }

        if ($opts['drying_step']) {
            $dryingStep = '';
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                if ($map_dryingStep) {
                    $value_raw = $this->GetArrayElem($jdata, 'dryingStep.value_raw', 0, $fnd);
                    if ($fnd) {
                        $dryingStep = $this->dryingStep2text($deviceId, $value_raw);
                    }
                } else {
                    $dryingStep = $this->GetArrayElem($jdata, 'dryingStep.value_localized', '', $fnd);
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "DryingStep" to ' . $dryingStep, 0);
                $this->SaveValue('DryingStep', $dryingStep, $is_changed);
            }
        }

        if ($opts['ventilation_step']) {
            $ventilationStep = '';
            if ($status == self::$STATE_OFF) {
                $fnd = true;
            } else {
                if ($map_ventilationStep) {
                    $value_raw = $this->GetArrayElem($jdata, 'ventilationStep.value_raw', 0, $fnd);
                    if ($fnd) {
                        $ventilationStep = $this->ventilationStep2text($deviceId, $value_raw);
                    }
                } else {
                    $ventilationStep = $this->GetArrayElem($jdata, 'ventilationStep.value_localized', '', $fnd);
                }
            }
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "VentilationStep" to ' . $ventilationStep, 0);
                $this->SaveValue('VentilationStep', $ventilationStep, $is_changed);
            }
        }

        if ($opts['fridge_temp']) {
            $zone = $opts['fridge_zone'] - 1;
            if ($zone >= 0) {
                $targetTemperature = $this->GetArrayElem($jdata, 'targetTemperature.' . $zone . '.value_localized', 0, $fnd);
                if ($fnd) {
                    if ($targetTemperature <= -326) {
                        $targetTemperature = 0;
                    }
                    $this->SendDebug(__FUNCTION__, 'set "Fridge_TargetTemperature" to ' . $targetTemperature, 0);
                    $this->SaveValue('Fridge_TargetTemperature', $targetTemperature, $is_changed);
                }

                $temperature = $this->GetArrayElem($jdata, 'temperature.' . $zone . '.value_localized', 0, $fnd);
                if ($fnd) {
                    if ($temperature <= -326) {
                        $temperature = 0;
                    }
                    $this->SendDebug(__FUNCTION__, 'set "Fridge_Temperature" to ' . $temperature, 0);
                    $this->SaveValue('Fridge_Temperature', $temperature, $is_changed);
                }
            }
        }

        if ($opts['freezer_temp']) {
            $zone = $opts['freezer_zone'] - 1;
            if ($zone >= 0) {
                $targetTemperature = $this->GetArrayElem($jdata, 'targetTemperature.' . $zone . '.value_localized', 0, $fnd);
                if ($fnd) {
                    if ($targetTemperature <= -326) {
                        $targetTemperature = 0;
                    }
                    $this->SendDebug(__FUNCTION__, 'set "Freezer_TargetTemperature" to ' . $targetTemperature, 0);
                    $this->SaveValue('Freezer_TargetTemperature', $targetTemperature, $is_changed);
                }

                $temperature = $this->GetArrayElem($jdata, 'temperature.' . $zone . '.value_localized', 0, $fnd);
                if ($fnd) {
                    if ($temperature <= -326) {
                        $temperature = 0;
                    }
                    $this->SendDebug(__FUNCTION__, 'set "Freezer_Temperature" to ' . $temperature, 0);
                    $this->SaveValue('Freezer_Temperature', $temperature, $is_changed);
                }
            }
        }

        if ($opts['oven_temp']) {
            $targetTemperature = $this->GetArrayElem($jdata, 'targetTemperature.0.value_localized', 0, $fnd);
            if ($fnd) {
                if ($targetTemperature <= -326) {
                    $targetTemperature = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "Oven_TargetTemperature" to ' . $targetTemperature, 0);
                $this->SaveValue('Oven_TargetTemperature', $targetTemperature, $is_changed);
            }

            $temperature = $this->GetArrayElem($jdata, 'temperature.0.value_localized', 0, $fnd);
            if ($fnd) {
                if ($temperature <= -326) {
                    $temperature = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "Oven_Temperature" to ' . $temperature, 0);
                $this->SaveValue('Oven_Temperature', $temperature, $is_changed);
            }
        }

        if ($opts['core_temp']) {
            $targetTemperature = $this->GetArrayElem($jdata, 'coreTargetTemperature.0.value_localized', 0, $fnd);
            if ($fnd) {
                if ($targetTemperature <= -326) {
                    $targetTemperature = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "Core_TargetTemperature" to ' . $targetTemperature, 0);
                $this->SaveValue('Core_TargetTemperature', $targetTemperature, $is_changed);
            }

            $temperature = $this->GetArrayElem($jdata, 'coreTemperature.0.value_localized', 0, $fnd);
            if ($fnd) {
                if ($temperature <= -326) {
                    $temperature = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "Core_Temperature" to ' . $temperature, 0);
                $this->SaveValue('Core_Temperature', $temperature, $is_changed);
            }
        }

        if ($opts['door']) {
            $signalDoor = (bool) $this->GetArrayElem($jdata, 'signalDoor', false, $fnd);
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "Door" to ' . $this->bool2str($signalDoor), 0);
                $this->SaveValue('Door', $signalDoor, $is_changed);
            }
        }

        if ($opts['ecoFeedback_Water']) {
            $ecoFeedback = $this->GetArrayElem($jdata, 'ecoFeedback', '', $fnd);
            if ($fnd) {
                if ($status == self::$STATE_END_PROGRAMMED) {
                    $ecoFeedback = false;
                }
                if ($ecoFeedback != false) {
                    $currentWaterConsumption = $this->GetArrayElem($ecoFeedback, 'currentWaterConsumption.value', 0);
                    $waterforecast = $this->GetArrayElem($ecoFeedback, 'waterforecast', 0);
                    $estimatedWaterConsumption = $currentWaterConsumption * (float) $waterforecast * 100;
                    $this->SendDebug(__FUNCTION__, 'WaterConsumption: current=' . $currentWaterConsumption . ', forecast=' . $waterforecast . ', estimated=' . $estimatedWaterConsumption, 0);
                } else {
                    $currentWaterConsumption = $this->GetValue('CurrentWaterConsumption');
                    if ($currentWaterConsumption > 0) {
                        $this->SendDebug(__FUNCTION__, 'set "LastWaterConsumption" to ' . $currentWaterConsumption, 0);
                        $this->SaveValue('LastWaterConsumption', $currentWaterConsumption, $is_changed);
                    }
                    $currentWaterConsumption = 0;
                    $estimatedWaterConsumption = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "CurrentWaterConsumption" to ' . $currentWaterConsumption, 0);
                $this->SaveValue('CurrentWaterConsumption', $currentWaterConsumption, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set "EstimatedWaterConsumption" to ' . $estimatedWaterConsumption, 0);
                $this->SaveValue('EstimatedWaterConsumption', $estimatedWaterConsumption, $is_changed);
            }
        }

        if ($opts['ecoFeedback_Energy']) {
            $ecoFeedback = $this->GetArrayElem($jdata, 'ecoFeedback', '', $fnd);
            if ($fnd) {
                if ($status == self::$STATE_END_PROGRAMMED) {
                    $ecoFeedback = false;
                }
                if ($ecoFeedback != false) {
                    $currentEnergyConsumption = $this->GetArrayElem($ecoFeedback, 'currentEnergyConsumption.value', 0);
                    $energyforecast = $this->GetArrayElem($ecoFeedback, 'energyforecast', 0);
                    $estimatedEnergyConsumption = $currentEnergyConsumption * (float) $energyforecast * 100;
                    $this->SendDebug(__FUNCTION__, 'EnergyConsumption: current=' . $currentEnergyConsumption . ', forecast=' . $energyforecast . ', estimated=' . $estimatedEnergyConsumption, 0);
                } else {
                    $currentEnergyConsumption = $this->GetValue('CurrentEnergyConsumption');
                    if ($currentEnergyConsumption > 0) {
                        $this->SendDebug(__FUNCTION__, 'set "LastEnergyConsumption" to ' . $currentEnergyConsumption, 0);
                        $this->SaveValue('LastEnergyConsumption', $currentEnergyConsumption, $is_changed);
                    }
                    $currentEnergyConsumption = 0;
                    $estimatedEnergyConsumption = 0;
                }
                $this->SendDebug(__FUNCTION__, 'set "CurrentEnergyConsumption" to ' . $currentEnergyConsumption, 0);
                $this->SaveValue('CurrentEnergyConsumption', $currentEnergyConsumption, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set "EstimatedEnergyConsumption" to ' . $estimatedEnergyConsumption, 0);
                $this->SaveValue('EstimatedEnergyConsumption', $estimatedEnergyConsumption, $is_changed);
            }
        }

        if ($opts['batteryLevel']) {
            $batteryLevel = $this->GetArrayElem($jdata, 'batteryLevel', 0, $fnd);
            if ($fnd) {
                $this->SendDebug(__FUNCTION__, 'set "BatteryLevel" to ' . $batteryLevel, 0);
                $this->SaveValue('BatteryLevel', (int) $batteryLevel, $is_changed);
            }
        }

        if ($is_changed) {
            $this->SetValue('LastChange', $now);
        }
    }

    private function DecodeActions($source, $actions)
    {
        $this->SendDebug(__FUNCTION__, 'source=' . $source . ', actions=' . print_r($actions, true), 0);

        $this->setEnabledActions($actions);

        $deviceId = $this->ReadPropertyInteger('deviceId');
        $opts = $this->getDeviceOptions($deviceId);

        if ($opts['enabled_action']) {
            if ($this->checkAction('Start', false)) {
                $b = true;
                $v = self::$ACTION_START;
            } elseif ($this->checkAction('Stop', false)) {
                $b = true;
                $v = self::$ACTION_STOP;
            } elseif ($this->checkAction('Pause', false)) {
                $b = true;
                $v = self::$ACTION_PAUSE;
            } else {
                $b = false;
                $v = self::$ACTION_UNDEF;
            }
            $this->SetValue('Action', $v);
            $this->MaintainAction('Action', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Action": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted('Action'), 0);
        }

        if ($opts['enabled_superfreezing']) {
            if ($this->checkAction('StartSuperfreezing', false)) {
                $b = true;
                $v = self::$ACTION_START;
            } elseif ($this->checkAction('StopSuperfreezing', false)) {
                $b = true;
                $v = self::$ACTION_STOP;
            } else {
                $b = false;
                $v = self::$ACTION_UNDEF;
            }
            $this->SetValue('Superfreezing', $v);
            $this->MaintainAction('Superfreezing', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Superfreezing": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted('Superfreezing'), 0);
        }

        if ($opts['enabled_supercooling']) {
            if ($this->checkAction('StartSupercooling', false)) {
                $b = true;
                $v = self::$ACTION_START;
            } elseif ($this->checkAction('StopSupercooling', false)) {
                $b = true;
                $v = self::$ACTION_STOP;
            } else {
                $b = false;
                $v = self::$ACTION_UNDEF;
            }
            $this->SetValue('Supercooling', $v);
            $this->MaintainAction('Supercooling', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Supercooling": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted('Supercooling'), 0);
        }

        if ($opts['enabled_light']) {
            if ($this->checkAction('LightEnable', false)) {
                $b = true;
                $v = self::$LIGHT_ENABLE;
            } elseif ($this->checkAction('LightDisable', false)) {
                $b = true;
                $v = self::$LIGHT_DISABLE;
            } else {
                $b = false;
                $v = self::$LIGHT_UNDEF;
            }
            $this->SetValue('Light', $v);
            $this->MaintainAction('Light', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Light": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted('Light'), 0);
        }

        if ($opts['enabled_starttime']) {
            if ($this->checkAction('SetStarttime', false)) {
                $b = true;
            } else {
                $b = false;
            }
            $this->MaintainAction('StartTime', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "StartTime": enabled=' . $this->bool2str($b), 0);
        }

        if ($opts['enabled_powersupply']) {
            if ($this->checkAction('PowerOn', false)) {
                $b = true;
                $v = self::$POWER_ON;
            } elseif ($this->checkAction('PowerOff', false)) {
                $b = true;
                $v = self::$POWER_OFF;
            } else {
                $b = false;
                $v = self::$POWER_UNDEF;
            }
            $this->SetValue('PowerSupply', $v);
            $this->MaintainAction('PowerSupply', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "PowerSupply": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted('PowerSupply'), 0);
        }

        if ($opts['enabled_fridge_temp']) {
            $zone = $opts['fridge_zone'];
            if ($zone > 0 && $this->checkAction('SetTargetTemperature_' . $zone, false)) {
                $b = true;
            } else {
                $b = false;
            }
            // Problem in der API: wenn SUPERCOOLING aktiv ist, darf die Zieltemperatur nicht geändert werden (@alsk1)
            if ($this->GetValue('State') == self::$STATE_SUPERCOOLING) {
                $b = false;
            }
            $this->MaintainAction('Fridge_TargetTemperature', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Fridge_TargetTemperature": enabled=' . $this->bool2str($b), 0);
        }

        if ($opts['enabled_freezer_temp']) {
            $zone = $opts['freezer_zone'];
            if ($zone > 0 && $this->checkAction('SetTargetTemperature_' . $zone, false)) {
                $b = true;
            } else {
                $b = false;
            }
            // Problem in der API: wenn SUPERFREEZING aktiv ist, darf die Zieltemperatur nicht geändert werden
            if ($this->GetValue('State') == self::$STATE_SUPERFREEZING) {
                $b = false;
            }
            $this->MaintainAction('Freezer_TargetTemperature', $b);
            $this->SendDebug(__FUNCTION__, 'MaintainAction "Freezer_TargetTemperature": enabled=' . $this->bool2str($b), 0);
        }

        if ($opts['enabled_operationmode']) {
            $modes = isset($actions['modes']) ? $actions['modes'] : [];
            $this->SendDebug(__FUNCTION__, 'modes=' . print_r($modes, true), 0);

            $names = [
                self::$OPERATIONMODE_SABBATH => 'OperationMode_Sabbath',
                self::$OPERATIONMODE_PARTY   => 'OperationMode_Party',
                self::$OPERATIONMODE_HOLIDAY => 'OperationMode_Holiday',
            ];
            foreach ($names as $mode => $name) {
                if ($modes != []) {
                    $b = true;
                    $v = in_array($mode, $modes) == false;
                } else {
                    $b = false;
                    $v = false;
                }
                $this->SetValue($name, $v);
                $this->MaintainAction($name, $b);
                $this->SendDebug(__FUNCTION__, 'MaintainAction "' . $name . '": enabled=' . $this->bool2str($b) . ', value=' . $this->GetValueFormatted($name), 0);
            }
        }
    }

    private function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return;
        }

        $this->SetUpdateInterval();

        $fabNumber = $this->ReadPropertyString('fabNumber');

        $SendData = [
            'DataID'   => '{AE164AF6-A49F-41BD-94F3-B4829AAA0B55}',
            'CallerID' => $this->InstanceID,
            'Function' => 'GetDeviceStatus',
            'Ident'    => $fabNumber
        ];
        $data = $this->SendDataToParent(json_encode($SendData));
        $jdata = @json_decode((string) $data, true);
        $this->DecodeDevice('Update', $jdata);

        $actions = $this->queryEnabledActions();
        $this->DecodeActions('Update', $actions);
    }

    private function programId2text($model, $id)
    {
        $id2txt = [
            0 => [
                0 => ''
            ],
        ];

        if (isset($id2txt[$model][$id])) {
            $txt = $this->Translate($id2txt[$model][$id]);
        } elseif (isset($id2txt[0][$id])) {
            $txt = $this->Translate($id2txt[0][$id]);
        } else {
            $txt = $this->Translate('unknown value') . ' ' . $id;
            $e = 'unknown value ' . $id;
            $this->SendDebug(__FUNCTION__, $e, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
        }
        return $txt;
    }

    private function programType2text($model, $type)
    {
        $type2txt = [
            0 => [
                0 => 'Normal operation mode',
                1 => 'Own program',
                2 => 'Automatic program',
                3 => 'Cleaning-/Care program',
            ],

            self::$DEVICE_TUMBLE_DRYER => [
                2 => 'Automatic plus',
                3 => 'Cotton',
            ],

            self::$DEVICE_DISHWASHER => [
                2 => 'Intensiv',
            ],
        ];

        if (isset($type2txt[$model][$type])) {
            $txt = $this->Translate($type2txt[$model][$type]);
        } elseif (isset($type2txt[0][$type])) {
            $txt = $this->Translate($type2txt[0][$type]);
        } else {
            $txt = $this->Translate('unknown value') . ' ' . $type;
            $e = 'unknown value ' . $type;
            $this->SendDebug(__FUNCTION__, $e, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
        }
        return $txt;
    }

    private function programPhase2text($model, $phase)
    {
        $phase2txt = [
            0 => [
                0 => 'Ready',
            ],
            self::$DEVICE_WASHING_MACHINE => [
                256 => 'Not running',
                257 => 'Pre-wash',
                258 => 'Soak',
                259 => 'Pre-wash',
                260 => 'Main wash',
                261 => 'Rinse',
                262 => 'Rinse hold',
                263 => 'Main wash',
                264 => 'Cooling down',
                265 => 'Drain',
                266 => 'Spin',
                267 => 'Anti-crease',
                268 => 'Finished',
                269 => 'Venting',
                270 => 'Starch stop',
                271 => 'Freshen-up + moisten',
                272 => 'Steam smoothing',
                279 => 'Hygiene',
                280 => 'Drying',
                285 => 'Disinfection',
                295 => 'Steam smoothing',
            ],

            self::$DEVICE_TUMBLE_DRYER => [
                512 => 'Not running',
                513 => 'Program running',
                514 => 'Drying',
                515 => 'Machine iron',
                516 => 'Hand iron',
                517 => 'Normal',
                518 => 'Normal plus',
                519 => 'Cooling down',
                520 => 'Hand iron',
                521 => 'Anti-crease',
                522 => 'Finished',
                523 => 'Extra dry',
                524 => 'Hand iron',
                526 => 'Moisten',
                528 => 'Timed drying',
                529 => 'Warm air',
                530 => 'Steam smoothing',
                531 => 'Comfort cooling',
                532 => 'Rinse out lint',
                533 => 'Rinses',
                534 => 'Smoothing',
                537 => 'Programmed',
                538 => 'Slightly dry',
                539 => 'Safety cooling',
            ],

            self::$DEVICE_DISHWASHER => [
                1792 => 'Not running',
                1793 => 'Reactivating',
                1794 => 'Pre-wash',
                1795 => 'Main wash',
                1796 => 'Rinse',
                1797 => 'Interim rinse',
                1798 => 'Final rinse',
                1799 => 'Drying',
                1800 => 'Finished',
                1801 => 'Pre-wash',
            ],

            self::$DEVICE_OVEN => [
                3072 => 'Not running',
                3073 => 'Heating up',
                3074 => 'In progress',
                3078 => 'Finished',
                3840 => 'Save energy',
            ],

            self::$DEVICE_STEAM_OVEN_COMBINATION => [
                3840 => 'Rinse',
                7938 => 'In progress',
                7940 => 'Heating up',
                7941 => 'Cooling down',
                7942 => 'Finished',
            ],
        ];

        if (isset($phase2txt[$model][$phase])) {
            $txt = $this->Translate($phase2txt[$model][$phase]);
        } elseif (isset($phase2txt[0][$phase])) {
            $txt = $this->Translate($phase2txt[0][$phase]);
        } else {
            $txt = $this->Translate('unknown value') . ' ' . $phase;
            $e = 'unknown value ' . $phase;
            $this->SendDebug(__FUNCTION__, $e, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
        }
        return $txt;
    }

    private function dryingStep2text($model, $step)
    {
        $this->SendDebug(__FUNCTION__, 'model=' . $model . ', step=' . $step, 0);
        $step2txt = [
            0 => [
                0 => 'Extra Dry',
                1 => 'Normal Plus',
                2 => 'Normal',
                3 => 'Slightly Dry',
                4 => 'Hand iron level 1',
                5 => 'Hand iron level 2',
                6 => 'Machine iron',
                7 => 'Smooth',
            ],
        ];

        if (isset($step2txt[$model][$step])) {
            $txt = $this->Translate($step2txt[$model][$step]);
        } elseif (isset($step2txt[0][$step])) {
            $txt = $this->Translate($step2txt[0][$step]);
        } else {
            $txt = $this->Translate('unknown value') . ' ' . $step;
            $e = 'unknown value ' . $step;
            $this->SendDebug(__FUNCTION__, $e, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
        }
        return $txt;
    }

    private function ventilationStep2text($model, $step)
    {
        $step2txt = [
            0 => [
                0 => 'None',
                1 => 'Step 1',
                2 => 'Step 2',
                3 => 'Step 3',
                4 => 'Step 4',
            ],
        ];

        if (isset($step2txt[$model][$step])) {
            $txt = $this->Translate($step2txt[$model][$step]);
        } elseif (isset($step2txt[0][$step])) {
            $txt = $this->Translate($step2txt[0][$step]);
        } else {
            $txt = $this->Translate('unknown value') . ' ' . $step;
            $e = 'unknown value ' . $step;
            $this->SendDebug(__FUNCTION__, $e, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $e, KL_NOTIFY);
        }
        return $txt;
    }

    private function checkAction($func, $verbose)
    {
        $enabled = false;

        $actions = $this->getEnabledActions();
        $processAction = isset($actions['processAction']) ? $actions['processAction'] : [];
        $light = isset($actions['light']) ? $actions['light'] : [];
        $startTime = isset($actions['startTime']) ? $actions['startTime'] : [];
        $targetTemperature = isset($actions['targetTemperature']) ? $actions['targetTemperature'] : [];
        $powerOff = isset($actions['powerOff']) ? $actions['powerOff'] : false;
        $powerOn = isset($actions['powerOn']) ? $actions['powerOn'] : false;
        $modes = isset($actions['modes']) ? $actions['modes'] : [];

        switch ($func) {
            case 'Start':
                if (in_array(self::$PROCESS_START, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'Stop':
                if (in_array(self::$PROCESS_STOP, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'Pause':
                if (in_array(self::$PROCESS_PAUSE, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'StartSuperfreezing':
                if (in_array(self::$PROCESS_START_SUPERFREEZING, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'StopSuperfreezing':
                if (in_array(self::$PROCESS_STOP_SUPERFREEZING, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'StartSupercooling':
                if (in_array(self::$PROCESS_START_SUPERCOOLING, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'StopSupercooling':
                if (in_array(self::$PROCESS_STOP_SUPERCOOLING, $processAction)) {
                    $enabled = true;
                }
                break;
            case 'LightEnable':
                if (in_array(self::$LIGHT_ENABLE, $light)) {
                    $enabled = true;
                }
                break;
            case 'LightDisable':
                if (in_array(self::$LIGHT_DISABLE, $light)) {
                    $enabled = true;
                }
                break;
            case 'PowerOn':
                if ($powerOn == true) {
                    $enabled = true;
                }
                break;
            case 'PowerOff':
                if ($powerOff == true) {
                    $enabled = true;
                }
                break;
            case 'SetStarttime':
                if ($startTime != []) {
                    $enabled = true;
                }
                break;
            case 'SetTargetTemperature_1':
                foreach ($targetTemperature as $t) {
                    if ($t['zone'] == 1) {
                        $enabled = true;
                        break;
                    }
                }
                break;
            case 'SetTargetTemperature_2':
                foreach ($targetTemperature as $t) {
                    if ($t['zone'] == 2) {
                        $enabled = true;
                        break;
                    }
                }
                break;
            case 'SetOperationMode_Normal':
                if (in_array($mode, self::$OPERATIONMODE_NORMAL, $modes)) {
                    $enabled = true;
                }
                break;
            case 'SetOperationMode_Sabbath':
                if (in_array($mode, self::$OPERATIONMODE_SABBATH, $modes)) {
                    $enabled = true;
                }
                break;
            case 'SetOperationMode_Party':
                if (in_array($mode, self::$OPERATIONMODE_PARTY, $modes)) {
                    $enabled = true;
                }
                break;
            case 'SetOperationMode_Holiday':
                if (in_array($mode, self::$OPERATIONMODE_HOLIDAY, $modes)) {
                    $enabled = true;
                }
                break;
            default:
                break;
        }

        $this->SendDebug(__FUNCTION__, 'action "' . $func . '" is ' . ($enabled ? 'enabled' : 'disabled'), 0);
        if ($verbose && !$enabled) {
            $this->LogMessage(__FUNCTION__ . ': action "' . $func . '" is not enabled for ' . IPS_GetName($this->InstanceID), KL_WARNING);
        }
        return $enabled;
    }

    private function CallAction($func, $action)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }
        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'func=' . $func . ', action=' . print_r($action, true), 0);

        $fabNumber = $this->ReadPropertyString('fabNumber');

        $SendData = [
            'DataID'   => '{AE164AF6-A49F-41BD-94F3-B4829AAA0B55}',
            'CallerID' => $this->InstanceID,
            'Function' => 'Action',
            'Ident'    => $fabNumber,
            'Action'   => $action
        ];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);
        $jdata = json_decode($data, true);

        return $jdata['Status'];
    }

    public function Start()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_START
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function Stop()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_STOP
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function Pause()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_PAUSE
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function StartSuperfreezing()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_START_SUPERFREEZING
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function StopSuperfreezing()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_STOP_SUPERFREEZING
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function StartSupercooling()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_START_SUPERCOOLING
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function StopSupercooling()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'processAction' => self::$PROCESS_STOP_SUPERCOOLING
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function LightEnable()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'light' => self::$LIGHT_ENABLE
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function LightDisable()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'light' => self::$LIGHT_DISABLE
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function PowerOn()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'powerOn' => true
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function PowerOff()
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'powerOff' => true
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function SetStarttime(int $hour, int $min)
    {
        if (!$this->checkAction(__FUNCTION__, true)) {
            return false;
        }

        $action = [
            'startTime' => [$hour, $min]
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function SetTargetTemperature(int $zone, int $temp)
    {
        if (!$this->checkAction(__FUNCTION__ . '_' . $zone, true)) {
            return false;
        }

        $actions = $this->getEnabledActions();
        $targetTemperature = isset($actions['targetTemperature']) ? $actions['targetTemperature'] : [];
        foreach ($targetTemperature as $t) {
            if ($t['zone'] == $zone) {
                $this->SendDebug(__FUNCTION__, 't=' . print_r($t, true), 0);
                if (isset($t['min']) && $temp < $t['min']) {
                    $this->SendDebug(__FUNCTION__, 'temp=' . $temp . ' is < min=' . $t['min'], 0);
                    return false;
                }
                if (isset($t['max']) && $temp > $t['max']) {
                    $this->SendDebug(__FUNCTION__, 'temp=' . $temp . ' is > max=' . $t['max'], 0);
                    return false;
                }
                break;
            }
        }

        $action = [
            'targetTemperature' => [
                [
                    'zone'  => $zone,
                    'value' => $temp,
                ],
            ],
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    public function SetOperationMode(int $mode)
    {
        $names = [
            self::$OPERATIONMODE_NORMAL  => 'SetOperationMode_Normal',
            self::$OPERATIONMODE_SABBATH => 'SetOperationMode_Sabbath',
            self::$OPERATIONMODE_PARTY   => 'SetOperationMode_Party',
            self::$OPERATIONMODE_HOLIDAY => 'SetOperationMode_Holiday',
        ];
        $b = isset($names[$mode]) ? $this->checkAction($names[$mode], true) : false;
        if ($b == false) {
            return false;
        }

        $action = [
            'modes' => $mode
        ];

        return $this->CallAction(__FUNCTION__, $action);
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            case 'UpdateData':
                $this->UpdateData();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $r = false;
        switch ($ident) {
            case 'StartTime':
                if ($value > 0) {
                    /*
                    $hour = (int) date('H', $value);
                    $min = (int) date('i', $value);
                     */
                    $sec = $value - time();
                    if ($sec < 0) {
                        $sec += 24 * 60 * 60;
                    }
                    if ($sec < 0) {
                        $sec %= 24 * 60 * 60;
                        $hour = floor($sec / 3600);
                        $sec %= 3600;
                        $min = floor($sec / 60);
                    }
                    if ($hour > 0 || $min > 0) {
                        $r = $this->SetStarttime($hour, $min);
                        if ($r) {
                            $this->SetValue($ident, $value);
                        }
                        $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' (hour=' . $hour . '. min=' . $min . ') => ret=' . $r, 0);
                    } else {
                        $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' (' . date('d.m.Y H:i:s', $value) . ') => ret=' . $r, 0);
                    }
                }
                break;
            case 'Action':
                switch ($value) {
                    case self::$ACTION_START:
                        $r = $this->Start();
                        break;
                    case self::$ACTION_PAUSE:
                        $r = $this->Pause();
                        break;
                    case self::$ACTION_STOP:
                        $r = $this->Stop();
                        break;
                    default:
                        $r = false;
                        break;
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'Superfreezing':
                switch ($value) {
                    case self::$ACTION_START:
                        $r = $this->StartSuperfreezing();
                        break;
                    case self::$ACTION_STOP:
                        $r = $this->StopSuperfreezing();
                        break;
                    default:
                        $r = false;
                        break;
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'Supercooling':
                switch ($value) {
                    case self::$ACTION_START:
                        $r = $this->StartSupercooling();
                        break;
                    case self::$ACTION_STOP:
                        $r = $this->StopSupercooling();
                        break;
                    default:
                        $r = false;
                        break;
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'Light':
                switch ($value) {
                    case self::$LIGHT_ENABLE:
                        $r = $this->LightEnable();
                        break;
                    case self::$LIGHT_DISABLE:
                        $r = $this->LightDisable();
                        break;
                    default:
                        $r = false;
                        break;
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'PowerSupply':
                switch ($value) {
                    case self::$POWER_ON:
                        $r = $this->PowerOn();
                        break;
                    case self::$POWER_OFF:
                        $r = $this->PowerOff();
                        break;
                    default:
                        $r = false;
                        break;
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'Fridge_TargetTemperature':
                $deviceId = $this->ReadPropertyInteger('deviceId');
                $opts = $this->getDeviceOptions($deviceId);
                $zone = $opts['fridge_zone'];
                $r = $this->SetTargetTemperature($zone, $value);
                if ($r) {
                    $this->SetValue($ident, $value);
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'Freezer_TargetTemperature':
                $deviceId = $this->ReadPropertyInteger('deviceId');
                $opts = $this->getDeviceOptions($deviceId);
                $zone = $opts['freezer_zone'];
                $r = $this->SetTargetTemperature($zone, $value);
                if ($r) {
                    $this->SetValue($ident, $value);
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'OperationMode_Sabbath':
                $r = $this->SetOperationMode($value ? self::$OPERATIONMODE_SABBATH : self::$OPERATIONMODE_NORMAL);
                if ($r) {
                    $this->SetValue($ident, $value);
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'OperationMode_Party':
                $r = $this->SetOperationMode($value ? self::$OPERATIONMODE_PARTY : self::$OPERATIONMODE_NORMAL);
                if ($r) {
                    $this->SetValue($ident, $value);
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            case 'OperationMode_Holiday':
                $r = $this->SetOperationMode($value ? self::$OPERATIONMODE_HOLIDAY : self::$OPERATIONMODE_NORMAL);
                if ($r) {
                    $this->SetValue($ident, $value);
                }
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $r, 0);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
        if ($r) {
            $this->MaintainTimer('UpdateData', 2000);
        }
    }

    private function setEnabledActions($actions)
    {
        $this->SetBuffer('EnabledActions', json_encode($actions));
    }

    private function getEnabledActions()
    {
        $data = $this->GetBuffer('EnabledActions');
        $actions = @json_decode((string) $data, true);
        return $actions;
    }

    private function queryEnabledActions()
    {
        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return false;
        }

        $fabNumber = $this->ReadPropertyString('fabNumber');
        $SendData = [
            'DataID'   => '{AE164AF6-A49F-41BD-94F3-B4829AAA0B55}',
            'CallerID' => $this->InstanceID,
            'Function' => 'GetDeviceActions',
            'Ident'    => $fabNumber
        ];
        $data = $this->SendDataToParent(json_encode($SendData));
        $actions = @json_decode((string) $data, true);
        return $actions;
    }
}
