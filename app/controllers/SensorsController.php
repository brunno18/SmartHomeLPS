<?php

use Phalcon\Http\Response;
use Phalcon\Http\Request;

class SensorsController extends ControllerBase
{
    public function indexAction()
    {
        $this->view->section_title = "Sensores";
        $this->view->form = new SensorsForm();
    }
    
    /**
     * Creates a new sensor
     */
    public function createAction()
    {
        $response = new Response();
        $response->setHeader("Content-Type", "application/json");
        
        $form = new SensorsForm;
        $sensor = new Devices();
        
        $data = $this->request->getPost();
        if ($form->isValid($data, $sensor)) {
            $sensor->category = Devices::SENSOR;
            $sensor->status = "OFF";
            if ($sensor->save()) {
                $response->setStatusCode(200, "Ok");
                $response->setJsonContent(
                    array(
                        "sensor" => array(
                            "id" => $sensor->id,
                            "name" => $sensor->name,
                            "type" => $sensor->type,
                            "description" => $sensor->description,
                        )
                    )
                );
            }
            else {
                $response->setStatusCode(409, "Conflict");
                
                $messages = array();
                foreach ($sensor->getMessages() as $message) {
                    array_push($messages, $message->getMessage());
                }
                
                $response->setJsonContent(
                    array(
                        "error" => array(
                            "code"   => 409,
                            "message" => $messages,
                            "title" => "Conflict"
                        )
                    )
                );
            }
        }
        else {
            $response->setStatusCode(400, "Bad Request");
            
            $messages = array();
            foreach ($form->getMessages() as $message) {
                array_push($messages, $message->getMessage());
            }
            
            $response->setJsonContent(
                array(
                    "error" => array(
                        "code"   => 400,
                        "message" => $messages,
                        "title" => "Bad Request"
                    )
                )
            );
        }
        
        $response->send();
    }
    
    public function deleteAction($id)
    {
        $response = new Response();
        $response->setHeader("Content-Type", "application/json");
        
        $sensor = Devices::findFirst($id);
        if (empty($sensor)) {
            $response->setStatusCode(404, "Not Found");    
            $response->setJsonContent(
                array(
                    "error" => array(
                        "code"    => 404,
                        "message" => "Não foi possível encontrar o sensor informado",
                        "title"   => "Not Found"
                    )
                )
            );
        }
        else {
            if ($sensor->delete()) {
                $response->setStatusCode(200, "Ok");
                $response->setJsonContent(
                    array(
                        "sensor" => array(
                            "id" => $sensor->id,
                            "type" => $sensor->type,
                            "description" => $sensor->description
                        )
                    )
                );
            }
            else {
                $messages = array();
                foreach ($sensor->getMessages() as $message) {
                    array_push($messages, $message->getMessage());
                }
                
                $response->setStatusCode(409, "Conflict");
                $response->setJsonContent(
                    array(
                        "error" => array(
                            "code"   => 409,
                            "message" => $messages,
                            "title" => "Conflict"
                        )
                    )
                );
            }
        }
        
        $response->send();
    }
    
    public function searchAction()
    {
        $this->view->disable();
        
        $columns = array('id', 'name', 'type', 'status');
        $query = Devices::query();
        $query->columns($columns);
        
        $where = "";
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            $filter = new \Phalcon\Filter();
            $search = $filter->sanitize($_GET['sSearch'], "string");
            for ( $i=0 ; $i<count($columns) ; $i++ )
            {
                $where .= $columns[$i] . " LIKE '%". $search ."%' OR ";
            }
            $where = substr_replace( $where, "", -3 );
        }

        $order = "";
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            for ( $i=0 ; $i< $_GET['iSortingCols']; $i++ )
            {
                if ( $_GET[ 'bSortable_' . $_GET['iSortCol_'.$i] ] == "true" )
                {
                    $order .= $columns[ $_GET['iSortCol_'.$i] ] . " " . $_GET['sSortDir_'.$i] .", ";
                }
            }
            $order = substr_replace( $order, "", -2 );
        }
        
        $limit = array("", "");
        if ( isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != -1 )
        {
            $limit = array($_GET['iDisplayLength'], $_GET['iDisplayStart']);
        }
        
        if (empty($where)) {
            $where = "true";
        }
        
        $whereCategory = "category = '" . Devices::SENSOR . "'";
        
        $query->where($where);
        $query->andWhere($whereCategory);
        $query->orderBy($order);
        $query->limit($limit[0], $limit[1]);
        $data = $query->execute();
        
        $iTotalRecords = Devices::count(array("conditions" => $whereCategory));
        $iTotalDisplayRecords = Devices::count(array("conditions" => $whereCategory . " AND " . $where));
        
        $json = array(
            "sEcho" => $_GET['sEcho'],
            "iTotalRecords" => $iTotalRecords,
            "iTotalDisplayRecords" => $iTotalDisplayRecords,
            "aaData" => array()
        );
        
        
        foreach ($data as $sensor) {
            $row = array();
            
            $row['id'] = $sensor->id;
            $row['name'] = $sensor->name;
            $row['type'] = $this->t->_($sensor->type);
            $row['status'] = $sensor->status;
            
            $json['aaData'][] = $row;
        }
        
        echo json_encode($json);
    }
    
    public function infoAction($type)
    {
        $response = new Response();
        $response->setHeader("Content-Type", "application/json");
        
        $configSensor = $this->searchInConfig($type);
        
        $response->setStatusCode(200, "Ok");
        $response->setJsonContent(
            array(
                "sensor" => array(
                    "dataType" => $configSensor['type']
                )
            )
        );
        
        $response->send();
    }
    
    private function searchInConfig($name)
    {
        $configSensors = $this->config->get("smarthome")->get("sensors");
        
        foreach($configSensors as $configSensor) {
            if ($name === $configSensor->name) {
                return $configSensor;
            }
        }
        
        return null;
    }
}