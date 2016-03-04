<?php
/**
 * Created by Anton Repin.
 * Date: 2/22/16
 * Time: 1:19 AM
 */

namespace Core\Server;

class RouterPathHolder
{

    /** @var RouterPathNode | null  */
    private $root = null;
    /** @var RouterPathNode | null  */
    private $current = null;

    function addRoot(RouterPathObject &$rpo){
        $root = new RouterPathNode();
        $root->routeObject = &$rpo;
        $this->root = $root;
        $this->current = $root;
    }

    function addNext(RouterPathObject &$rpo){
        $new = new RouterPathNode();
        $new->routeObject = &$rpo;
        $this->current->next = &$new;
        $this->current = $new;
    }

    /**
     * @return RouterPathNode|null
     */
    function getCurrent() {
        return $this->current;
    }

    /**
     * @return RouterPathNode|null
     */
    function getRoot() {
        return $this->root;
    }

    /**
     * @return RouterPathObject|null
     */
    function getRootObject() {
        if(isset($this->root))
            return $this->root->routeObject;
        return null;
    }

}