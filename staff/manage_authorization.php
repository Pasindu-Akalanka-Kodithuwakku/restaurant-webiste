<?php
    /* Manage Authorization */

    $authorized_devices = [
        ['127.0.0.1', ''],
        ['::1', '']
    ];

    /*
                ** Guide - Pages Numbers **

            Page Name                 Page Number

        Manage Menu Items                 01
        Manage Restaurant Tables          02
        Manage Kitchen Section            03
        Manage Bar Section                04
        Manage Login Customers            05
        Manage Cashier Room               06
        Navigate Menu Page                07
    */

    $authorized_pages_per_device = [
        ['01', '02', '03', '04', '05', '06', '07'],
        ['01', '02', '03', '04', '05', '06', '07']
    ];

    /* When add new device details, Uncomment the 34 line and see the login device details. after added device details please comment again 34 line. */
    //die('Login Device IP Address</br>' .$_SERVER['REMOTE_ADDR'] .'</br></br>logged In Device Web Browser ID</br>' .$_SERVER['HTTP_USER_AGENT']);
?>
