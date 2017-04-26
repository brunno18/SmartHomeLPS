<?php

use Phalcon\Http\Response;
use Phalcon\Http\Request;

use SmartHomeLPS\Services\ServicesManager;

class EventController extends ControllerBase
{
    public function notifyAction()
    {
        //Checa se tem que enviar alerta
        //Checa se tem que atuar em algum dispositivo a partir do perfil ativo
        
        $data = json_decode(file_get_contents( 'php://input' ));
        
        $alertsTemplate = AlertsTemplate::find("space_id = " . substr($data->data[0]->id, -1));
        
        $alerts = array();
        foreach($alertsTemplate as $alertTemplate) {
            if(property_exists($data->data[0],$alertTemplate->sensor)) {
                if ($alertTemplate->isActive()) {
                    $alert = new Alerts();
                    $alert->date = time();
                    $alert->template = $alertTemplate;
                    array_push($alerts, $alert);
                }
            }
            
        }
        
        $alertServices = ServicesManager::getAlertServices();
        foreach($alertServices as $alertService) {
            foreach($alerts as $alert) {
                $alertService->alert($alert);
            }
        }
    }
}