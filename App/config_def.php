<?php function applicationConfig(){return[
    /* -----------------------------------------------------------------
    |
    |                           DEV ENVIRONMENT
    |
    /* ----------------------------------------------------------------*/

    /* ------------------------------------------------------------------
     *
     *  Database parameters for connection
     *
     * -----------------------------------------------------------------*/

    # [Mysql Default Connection]
    'mysql_connection' => array(
        'type'=>'mysql',
        'host'=>'localhost',
        'port'=>'',
        'user' => '',
        'password' => '',
        'instance' => ''
    ),

    # [Mysql Custom Connection] for [ $db->useConnection('mysql_userconnection') ]
    'mysql_customconn' => array(
        'type'=>'mysql',
        'host'=>'localhost',
        'port'=>'',
        'user' => '',
        'password' => '',
        'instance' => ''
    ),


    /* ------------------------------------------------------------------
     *
     *  Parameters Specific for Marketplaces
     *
     * -----------------------------------------------------------------*/


    /* ------------------------------------------------------------------
     *
     *  Application Path parameters
     *
     * -----------------------------------------------------------------*/



];}