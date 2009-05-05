<?php // $Id$

    // vim: expandtab sw=4 ts=4 sts=4:

    if ( count( get_included_files() ) == 1 )
    {
        die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
    }

    $claroline->notification->addListener( 'learningpath_created',      'modificationDefault' );
    $claroline->notification->addListener( 'learningpath_visible',      'modificationDefault' );
    $claroline->notification->addListener( 'learningpath_invisible',    'modificationDelete' );
    $claroline->notification->addListener( 'learningpath_deleted',      'modificationDelete' );
?>