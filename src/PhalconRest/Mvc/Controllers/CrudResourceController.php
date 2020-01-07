<?php
namespace PhalconRest\Mvc\Controllers;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;
use PhalconApi\Constants\ErrorCodes;
use PhalconApi\Constants\PostedDataMethods;
use PhalconApi\Exception;

class CrudResourceController extends ResourceController
{
    /*** ALL ***/

    public function all()
    {
        if($this->request->getQuery('stats')==1)
        {
            $data = $this->getAllData();
            $response = $this->getAllResponse($data);
            return $response;     
        }
        
        $this->beforeHandle();
        $this->beforeHandleRead();
        $this->beforeHandleAll();

        
        $data = $this->getAllData();
        
        
        if (!$this->allAllowed($data)) {
            return $this->onNotAllowed();
        }

        $response = $this->getAllResponse($data);

        
        $this->afterHandleAll($data, $response);
        $this->afterHandleRead();
        $this->afterHandle();

        return $response;
    }


    protected function afterGetAllData($data)
    {
        error_log(print_r($data,true));
        
        return $data;  
    }
    
    protected function beforeHandle()
    {
       
    }

    protected function beforeHandleRead()
    {
        
    }

    protected function beforeHandleAll()
    {
        
    }

    protected function getAllData()
    {
        $phqlBuilder = $this->phqlQueryParser->fromQuery($this->query, $this->getResource());

        $this->modifyReadQuery($phqlBuilder);
        $this->modifyAllQuery($phqlBuilder);

        return $phqlBuilder->getQuery()->execute();
    }

    protected function modifyReadQuery(QueryBuilder $query)
    {
    }

    protected function modifyAllQuery(QueryBuilder $query)
    {
    }

    protected function allAllowed($data)
    {
        return true;
    }

    protected function onNotAllowed()
    {
        throw new Exception(ErrorCodes::ACCESS_DENIED, 'Operation is not allowed');
    }

    protected function getAllResponse($data)
    {
        return $this->createResourceCollectionResponse($data);
    }

    protected function afterHandleAll($data, &$response)
    { 
    }

    protected function afterHandleRead()
    {
    }

    protected function afterHandle()
    {
    }

    /*** FIND ***/

    public function find($uuid)
    {
        $this->beforeHandle();
        $this->beforeHandleRead();
        $this->beforeHandleFind($uuid);

        $item = $this->getFindData($uuid);

        if (!$item) {
            return $this->onItemNotFound($uuid);
        }

        
        if (!$this->findAllowed($uuid, $item)) {
            return $this->onNotAllowed();
        }

        
            
        $response = $this->getFindResponse($item);

        $this->afterHandleFind($item, $response);
        $this->beforeHandleRead();
        $this->afterHandle();

        return $response;
    }

    protected function beforeHandleFind($uuid)
    {
    }

    protected function getFindData($uuid)
    {
        $phqlBuilder = $this->phqlQueryParser->fromQuery($this->query, $this->getResource());

        $phqlBuilder->andWhere('[' . $this->getResource()->getModel() . '].' . $this->getModelPrimaryKey() . " = :uuid:", ['uuid' => $uuid])->limit(1);

        $this->modifyReadQuery($phqlBuilder);
        $this->modifyFindQuery($phqlBuilder, $uuid);
        $results = $phqlBuilder->getQuery()->execute();
        $item = count($results) >= 1 ? $results->getFirst() : null;
        if($item && get_class($item) != $this->getResource()->getModel()) {
                //Wrong model;
                $model = $this->getResource()->getModel();
                $_item = new $model();
                foreach($item as $i => $val) {
                    $_item->$i = $val;
                }
                
                $item = $_item;
                $item->afterFetch();
                
        }
        
        
             
        return $item;
    }
    
    protected function getCreatedData($uuid)
    {
        $phqlBuilder = $this->phqlQueryParser->fromQuery($this->query, $this->getResource());

        $phqlBuilder->andWhere('[' . $this->getResource()->getModel() . '].' . $this->getModelPrimaryKey() . " LIKE :uuid:", ['uuid' => $uuid])->limit(1);
        $results = $phqlBuilder->getQuery()->execute();
        return count($results) >= 1 ? $results->getFirst() : null;
    }

    protected function getModelPrimaryKey()
    {
        return $this->getResource()->getModelPrimaryKey();
    }

    protected function modifyFindQuery(QueryBuilder $query, $uuid)
    {
    }

    /*** ERROR HOOKS ***/

    protected function onItemNotFound($uuid)
    {
        throw new Exception(ErrorCodes::DATA_NOT_FOUND, 'Item was not found: '.$uuid, ['uuid' => $uuid]);
    }

    protected function findAllowed($uuid, $item)
    {
        return true;
    }

    protected function getFindResponse($item)
    {
        return $this->createResourceResponse($item);
    }

    protected function afterHandleFind($item, $response)
    {
    }

    /*** CREATE ***/

    public function create()
    {
        $this->beforeHandle();
        $this->beforeHandleWrite();
        $this->beforeHandleCreate();

        $data = $this->getPostedData();

        
        
        if (!$data || count($data) == 0) {
            return $this->onNoDataProvided();
        }

        if (!$this->postDataValid($data, false)) {
            return $this->onDataInvalid($data);
        }

        if (!$this->saveAllowed($data) || !$this->createAllowed($data)) {
            return $this->onNotAllowed();
        }

        $data = $this->transformPostData($data);

        $item = $this->createModelInstance();

        $newItem = $this->createItem($item, $data);

        if (!$newItem) {
            
            return $this->onCreateFailed($item, $data);
        }

     
        $primaryKey = $this->getModelPrimaryKey();
        
        //error_log('Primary key is :'.$primaryKey." = ".$newItem->$primaryKey);
        
        
        $responseData = $this->getCreatedData($newItem->$primaryKey);
        
        if(!$responseData) {
            error_log('responseData not exists');    
        }
        
        $response = $this->getCreateResponse($responseData, $data);
     
        
        
        $this->afterHandleCreate($newItem, $data, $response);
        $this->afterHandleWrite();
        $this->afterHandle();

        return $response;
    }

    protected function beforeHandleWrite()
    {
    }

    protected function beforeHandleCreate()
    {
    }

    function parse_raw_http_request(array &$a_data)
    {
      // read incoming data
      $input = file_get_contents('php://input');
    
        
        
      // grab multipart boundary from content type header
      preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
      $boundary = $matches[1];
    
      // split content by boundary and get rid of last -- element
      $a_blocks = preg_split("/-+$boundary/", $input);
      array_pop($a_blocks);
    
      // loop data blocks
      foreach ($a_blocks as $id => $block)
      {
        if (empty($block))
          continue;
    
        // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
    
        // parse uploaded files
        if (strpos($block, 'application/octet-stream') !== FALSE)
        {
          // match "name", then everything after "stream" (optional) except for prepending newlines 
          preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
        }
        // parse all other fields
        else
        {
          // match "name" and optional value in between newline sequences
          preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
        }
        $a_data[$matches[1]] = $matches[2];
      }        
    }

    /*** GENERAL HOOKS ***/

    protected function getPostedData()
    {
        
        $resourcePostedDataMode = $this->getResource()->getPostedDataMethod();
        $endpointPostedDataMode = $this->getEndpoint()->getPostedDataMethod();

        $postedDataMode = $resourcePostedDataMode;
        
        if ($endpointPostedDataMode != PostedDataMethods::AUTO) {
            $postedDataMode = $endpointPostedDataMode;
        }

        $postedData = null;

        switch ($postedDataMode) {

            case PostedDataMethods::POST:
                $postedData = $this->request->getPost();
            break;

            case PostedDataMethods::PUT:
                $postedData = $this->request->getPut();    
                if(!$postedData) $postedData = $this->request->getPost();            
            break;    
        
            case PostedDataMethods::JSON_BODY:
                $postedData = $this->request->getJsonRawBody(true);
            break;

            case PostedDataMethods::AUTO:
            default:
                $postedData = $this->request->getPostedData($this->getEndpoint()->getHttpMethod());
        }

        
  
        return $postedData;
    }

    protected function onNoDataProvided()
    {
        $dev = [
            'METHOD' => $_SERVER['REQUEST_METHOD'],
            'POST' => $this->request->getPost(),
            'POST' => $this->request->getPut(),
            'GET' => $this->request->getQuery(),
        ];
        
        throw new Exception(ErrorCodes::POST_DATA_NOT_PROVIDED, 'No post-data provided',$dev);
    }

    protected function postDataValid($data, $isUpdate)
    {
        return true;
    }

    protected function onDataInvalid($data)
    {
        throw new Exception(ErrorCodes::POST_DATA_INVALID, 'Post-data is invalid', ['data' => $data]);
    }

    protected function saveAllowed($data)
    {
        return true;
    }

    protected function createAllowed($data)
    {
        return true;
    }

    protected function transformPostData($data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $this->transformPostDataValue($key, $value, $data);
        }

        return $result;
    }

    protected function transformPostDataValue($key, $value, $data)
    {
        return $value;
    }

    /**
     * @return Model
     */
    protected function createModelInstance()
    {
        $modelClass = $this->getResource()->getModel();

        /** @var Model $item */
        $item = new $modelClass();

        return $item;
    }

    /**
     * @param Model $item
     * @param $data
     *
     * @return Model Created item
     */
    protected function createItem(Model $item, $data)
    {
        
        $this->beforeAssignData($item, $data);
        $item->assign($data, null, $this->whitelistCreate());
        $this->afterAssignData($item, $data);

        $this->beforeSave($item);
        $this->beforeCreate($item);

        $success = $item->create();

        if ($success) {
            
            
            $this->afterCreate($item);
            $this->afterSave($item);
        }

        return $success ? $item : null;
    }

    protected function beforeAssignData(Model $item, $data)
    {
    }

    protected function afterAssignData(Model $item, $data)
    {
    }

    protected function beforeSave(Model $item)
    {
    }

    protected function beforeCreate(Model $item)
    {
    }

    protected function afterCreate(Model $item)
    {
    }

    protected function afterSave(Model $item)
    {
    }

    protected function onCreateFailed(Model $item, $data)
    {
        throw new Exception(ErrorCodes::DATA_FAILED, 'Unable to create item', [
            'messages' => $this->_getMessages($item->getMessages()),
            'data' => $data,
            'item' => $item->toArray()
        ]);
    }

    private function _getMessages($messages)
    {
        $messages = isset($messages) ? $messages : [];

        return array_map(function (Model\Message $message) {
            return $message->getMessage();
        }, $messages);
    }

    protected function getCreateResponse($createdItem, $data)
    {
        return $this->createResourceOkResponse($createdItem);
    }

    protected function afterHandleCreate(Model $createdItem, $data, $response)
    {
    }

    protected function afterHandleWrite()
    {
    }

    /*** UPDATE ***/

    public function update($uuid)
    {
        $this->beforeHandle();
        $this->beforeHandleWrite();
        $this->beforeHandleUpdate($uuid);

        $data = $this->getPostedData();
        $item = $this->getItem($uuid);

        if (!$item) {
            return $this->onItemNotFound($uuid);
        }

        if (!$data || count($data) == 0) {
            return $this->onNoDataProvided();
        }

        if (!$this->postDataValid($data, true)) {
            return $this->onDataInvalid($data);
        }

        if (!$this->saveAllowed($data) || !$this->updateAllowed($item, $data)) {
            return $this->onNotAllowed();
        }

        $data = $this->transformPostData($data);

        $newItem = $this->updateItem($item, $data);

        if (!$newItem) {
            return $this->onUpdateFailed($item, $data);
        }

        $primaryKey = $this->getModelPrimaryKey();
        $responseData = $this->getFindData($newItem->$primaryKey);

        $response = $this->getUpdateResponse($responseData, $data);

        $this->afterHandleUpdate($newItem, $data, $response);
        $this->afterHandleWrite();
        $this->afterHandle();

        return $response;
    }

    protected function beforeHandleUpdate($uuid)
    {
    }

    /**
     * @param $id
     *
     * @return Model
     */
    protected function getItem($uuid)
    {
        $modelClass = $this->getResource()->getModel();
        return $modelClass::findFirst([
            'conditions' => 'uuid=:uuid: ',
            'bind' => ['uuid'=> $uuid ]
        ]);
    }

    protected function updateAllowed(Model $item, $data)
    {
        return true;
    }

    protected function whitelist()
    {
        return [];
    }

    protected function whitelistCreate()
    {
        return $this->whitelist();
    }

    protected function whitelistUpdate()
    {
        return $this->whitelist();
    }


    /**
     * @param Model $item
     * @param $data
     *
     * @return Model Updated model
     */
    protected function updateItem(Model $item, $data)
    {
        
        //error_log("updateItem: ".print_r($data,true));
        
        $this->beforeAssignData($item, $data);
        $item->assign($data, null, $this->whitelistUpdate());
        $this->afterAssignData($item, $data);
        
        $this->beforeSave($item);
        $this->beforeUpdate($item);

        try {
             
            
                
            $success = $item->update();

            if ($success) {
                
                $this->afterUpdate($item);
                $this->afterSave($item);
                
                
            } else {
                error_log("error");
                error_log('Error: '.join(",",$item->getMessages())." ".__FILE__.":".__LINE__." ");    
            }
    
            return $success ? $item : null;
            
        } catch(\Exception $e) {
            error_log("==================================================================================");
            error_log('Exception: '.$e->getMessage()." ".__FILE__.":".__LINE__." ");
            error_log(print_r($e->getTraceAsString(),true));
            error_log("==================================================================================");
            return null;
        }
    }

    protected function beforeUpdate(Model $item)
    {
    }

    protected function afterUpdate(Model $item)
    {
    }

    protected function onUpdateFailed(Model $item, $data)
    {
        throw new Exception(ErrorCodes::DATA_FAILED, 'Unable to update item', [
            'messages' => $this->_getMessages($item->getMessages()),
            'data' => $data,
            'item' => $item->toArray()
        ]);
    }

    protected function getUpdateResponse($updatedItem, $data)
    {
        return $this->createResourceOkResponse($updatedItem);
    }

    protected function afterHandleUpdate(Model $updatedItem, $data, $response)
    {
    }

    /*** REMOVE ***/

    public function remove($uuid)
    {
        $this->beforeHandle();
        $this->beforeHandleWrite();
        $this->beforeHandleRemove($uuid);

        $item = $this->getItem($uuid);

        if (!$item) {
            return $this->onItemNotFound($uuid);
        }

        if (!$this->removeAllowed($item)) {
            return $this->onNotAllowed();
        }

        $success = $this->removeItem($item);

        if (!$success) {
            return $this->onRemoveFailed($item);
        }

        $response = $this->getRemoveResponse($item);

        $this->afterHandleRemove($item, $response);
        $this->afterHandleWrite();
        $this->afterHandle();

        return $response;
    }

    protected function beforeHandleRemove($uuid)
    {
    }

    protected function removeAllowed(Model $item)
    {
        return true;
    }

    /**
     * @param Model $item
     *
     * @return bool Remove succeeded/failed
     */
    protected function removeItem(Model $item)
    {
        $this->beforeRemove($item);

        $success = $item->delete();

        if ($success) {
            $this->afterRemove($item);
        }

        return $success;
    }

    protected function beforeRemove(Model $item)
    {
    }

    protected function afterRemove(Model $item)
    {
    }

    protected function onRemoveFailed(Model $item)
    {
        throw new Exception(ErrorCodes::DATA_FAILED, 'Unable to remove item', [
            'messages' => $this->_getMessages($item->getMessages()),
            'item' => $item->toArray()
        ]);
    }

    protected function getRemoveResponse(Model $removedItem)
    {
        return $this->createOkResponse();
    }

    protected function afterHandleRemove(Model $removedItem, $response)
    {
    }
}
