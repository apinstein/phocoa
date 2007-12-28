<?php

// Created by PHOCOA WFModelCodeGen on {{php}}echo date('r');{{/php}}

class module_{{$moduleName}} extends WFModule
{
    function defaultPage() { return 'list'; }

    // this function should throw an exception if the user is not permitted to edit (add/edit/delete) in the current context
    function verifyEditingPermission($page)
    {
        // example
        // $authInfo = WFAuthorizationManager::sharedAuthorizationManager()->authorizationInfo();
        // if ($authInfo->userid() != $page->sharedOutlet('{{$sharedEntityId}}')->selection()->getUserId()) throw( new Exception("You don't have permission to edit {{$entityName}}.") );
    }
}

class module_{{$moduleName}}_list
{
    function parameterList()
    {
        return array('paginatorState');
    }
    
    function parametersDidLoad($page, $params)
    {
        $page->sharedOutlet('paginator')->readPaginatorStateFromParams($params);
    }
    
    function noAction($page, $params)
    {
        $this->search($page, $params);
    }
    
    function search($page, $params)
    {   
        $query = $page->outlet('query')->value();
        $c = new Criteria();
        if (!empty($query))
        {   
            $querySubStr = '%' . str_replace(' ', '%', trim($query)) . '%';

            $c->add({{$entityName}}Peer::{{$descriptiveColumnConstantName}}, $querySubStr, Criteria::ILIKE);
        }

        $page->sharedOutlet('paginator')->setDataDelegate(new WFPagedPropelQuery($c, '{{$entityName}}Peer'));
        $page->sharedOutlet('{{$sharedEntityId}}')->setContent($page->sharedOutlet('paginator')->currentItems());
    }

    function setupSkin($page, $parameters, $skin)
    {   
        $skin->addHeadString('<link rel="stylesheet" type="text/css" href="' . $skin->getSkinDirShared() . '/form.css" />');
        $skin->setTitle('{{$entityName}} List');
    }
}

class module_{{$moduleName}}_edit
{
    function parameterList()
    {
        return array('{{$sharedEntityPrimaryKeyAttribute}}');
    }
    function parametersDidLoad($page, $params)
    {
        if ($page->sharedOutlet('{{$sharedEntityId}}')->selection() === NULL)
        {
            if ($params['{{$sharedEntityPrimaryKeyAttribute}}'])
            {
                $page->sharedOutlet('{{$sharedEntityId}}')->setContent(array({{$entityName}}Peer::retrieveByPK($params['{{$sharedEntityPrimaryKeyAttribute}}'])));
                $page->module()->verifyEditingPermission($page);
            }
            else
            {
                // prepare content for new
                $page->sharedOutlet('{{$sharedEntityId}}')->setContent(array(new {{$entityName}}()));
            }
        }
    }
    function save($page)
    {
        try {
            $page->sharedOutlet('{{$sharedEntityId}}')->selection()->save();
            $page->outlet('statusMessage')->setValue("{{$entityName}} saved successfully.");
        } catch (Exception $e) {
            $page->addError( new WFError($e->getMessage()) );
        }
    }
    function delete($page)
    {
        $page->module()->verifyEditingPermission($page);
        $page->module()->setupResponsePage('confirmDelete');
    }

    function setupSkin($page, $parameters, $skin)
    {   
        $skin->addHeadString('<link rel="stylesheet" type="text/css" href="' . $skin->getSkinDirShared() . '/form.css" />');
        if ($page->sharedOutlet('{{$sharedEntityId}}')->selection()->isNew())
        {
            $title = 'New {{$entityName}}';
        }
        else
        {
            $title = 'Edit {{$entityName}}:' . $page->sharedOutlet('{{$sharedEntityId}}')->selection()->valueForKeyPath('{{$descriptiveColumnName}}');
        }
        $skin->setTitle($title);
    }
}

class module_{{$moduleName}}_confirmDelete
{
    function parameterList()
    {
        return array('{{$sharedEntityPrimaryKeyAttribute}}');
    }
    function parametersDidLoad($page, $params)
    {
        // if we're a redirected action, then the {{$entityName}} object is already loaded. If there is no object loaded, try to load it from the object ID passed in the params.
        if ($page->sharedOutlet('{{$sharedEntityId}}')->selection() === NULL)
        {
            $objectToDelete = {{$entityName}}Peer::retrieveByPK($params['{{$sharedEntityPrimaryKeyAttribute}}']);
            if (!$objectToDelete) throw( new Exception("Could not load {{$entityName}} object to delete.") );
            $page->sharedOutlet('{{$sharedEntityId}}')->setContent(array($objectToDelete));
        }
        if ($page->sharedOutlet('{{$sharedEntityId}}')->selection() === NULL) throw( new Exception("Could not load {{$entityName}} object to delete.") );
    }
    function cancel($page)
    {
        $page->module()->setupResponsePage('edit');
    }
    function delete($page)
    {
        $page->module()->verifyEditingPermission($page);
        $myObj = $page->sharedOutlet('{{$sharedEntityId}}')->selection();
        $myObj->delete();
        $page->sharedOutlet('{{$sharedEntityId}}')->removeObject($myObj);
        $page->module()->setupResponsePage('deleteSuccess');
    }
}
class module_{{$moduleName}}_detail
{
    function parameterList()
    {
        return array('{{$sharedEntityPrimaryKeyAttribute}}');
    }
    function parametersDidLoad($page, $params)
    {
        $page->sharedOutlet('{{$sharedEntityId}}')->setContent(array({{$entityName}}Peer::retrieveByPK($params['{{$sharedEntityPrimaryKeyAttribute}}'])));
    }
}
