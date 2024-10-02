<?php

/**
 * SMART
 *
 * @link       http://www.sitelock.com
 * @since      2.0.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */

?>
    
<div class="wrap">
<!--[if lt IE 7]>  <div class="ie ie6 lte9 lte8 lte7"> <![endif]-->
<!--[if IE 7]>     <div class="ie ie7 lte9 lte8 lte7"> <![endif]-->
<!--[if IE 8]>     <div class="ie ie8 lte9 lte8"> <![endif]-->
<!--[if IE 9]>     <div class="ie ie9 lte9"> <![endif]-->
<!--[if gt IE 9]>  <div> <![endif]-->
<!--[if !IE]><!--> <div>             <!--<![endif]-->
    <div class="sitelock">
        <div class="wpslp_container">

            <div class="row">
                
                <?php if ( !$this->can_fix ) { ?>
                    <div class="span6">
                        
                        <div class="wpslp_summary_box">
                            <h2>Source Code Scan</h2><!-- <br /> -->
                        </div>
                        
                        
                        <?php


                            $table_rules = array(
                                'table' => array( 'class' => array(), 'cellspacing' => array() ),
                                'th' => array( 'id' => array(), 'class' => array(), 'scope' => array() ),
                                'tr' => array( 'class' => array() ),
                                'td' => array( 'class' => array() ),
                                'thead' => array(),
                                'tfoot' => array(),
                                'strong' => array()
                            );
                        
                            $new_scan = false;
                            
                            if ( is_array( $download_url ) && isset( $download_url[ 'status' ] ) )
                            {
                                if ( $download_url[ 'status' ] == 'error' )
                                {
                                    $status  = 'error';
                                    
                                    if ( $download_url[ 'code' ] == 'noblowfish' )
                                    {
                                        $message = 'Your current encryption methods are not supported.  Our servers require blowfish encryption.';
                                    }
                                    else if ( $download_url[ 'code' ] == 'servererror' )
                                    {
                                        $message = 'Server unable to process request.';
                                    }
                                    else if ( $download_url[ 'code' ] == 'scanpending' )
                                    {
                                        $message = 'Scans can only be submitted one at time.  Once your current scan finishes then you will be able to submit an additional scan.';
                                    }
                                    else
                                    {
                                        $message = 'Unable to zip files automatically.  Make sure you have mcrypt_encrypt() function enabled on your server as well as SimpleXML';
                                    }
                                }
                                else if ( $download_url[ 'status' ] == 'success' )
                                {
                                    $new_scan = true;
                                    $status   = 'success';
                                    $message  = 'Your scan has been queued, it can take more than an hour to complete.  Please refresh this page periodically to view results.';
                                }
                            }
                            
                            if ( isset( $current_scan ) )
                            {
                                ?>
                                <p><strong>Results of your most recent Source Code Scan.</strong>
                                <?php if ( !$new_scan && $current_scan[ 'message' ][ 'state' ] == 'complete' ) : ?>
                                    <br />Status: <strong><?php echo esc_attr( $current_scan[ 'message' ][ 'result' ] ); ?></strong>
                                <?php else : ?>
                                
                                    <?php
                                    
                                        $status = !empty( $current_scan[ 'message' ][ 'state' ] ) ? $current_scan[ 'message' ][ 'state' ] : '--';
                                        
                                        switch ( $status )
                                        {
                                            
                                            case 'approved':
                                                #$status = 'Results pending - Zip format validated';
                                                #break;
                                                
                                            case 'scan_ready':
                                                #$status = 'Results pending - Preparing scan';
                                                #break;
                                                
                                            case 'scanning':
                                            case 'downloading':
                                            case 'downloaded':
                                                #$status = 'Results pending - Download zip file';
                                                $status = 'Scan in progress';
                                                break; 
                                        }
                                        
                                    ?>
                                
                                    <br />Status: <?php echo esc_attr( $new_scan ? '--' : $status ); ?>
                                    
                                <?php endif; ?>
                                </p>
                                <hr />
                                <?php
                            }
                            
                            if ( !empty( $message ) && !empty( $status ) )
                            {
                                ?>
                                <div id="message" class="<?php esc_attr( $status ); ?> notice notice-<?php echo esc_attr( $status ); ?> is-dismissible below-h2">
                                    <p>
                                        <?php echo esc_html( $message ) ; ?>
                                    </p>
                                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                                </div>
                                <?php
                                
                                if ( $new_scan )
                                {   
                                    if ( !empty( $this->all_files ) && is_array( $this->all_files ) )
                                    {
                                        $root = get_home_path();

                                        // organize all files into pages and files
                                        $pages = $files = array();
                                        
                                        foreach ( $this->all_files as $file )
                                        {
                                            $file = str_replace( $root, '', $file );
                                            
                                            if ( strpos( $file, Sitelock_Zip::ZIP_FOLDER . '/slwp_page_' ) !== false )
                                            {
                                                // this is a specific page
                                                $page_id = (int) str_replace( array( 'slwp_page_', self::DB_PAGE_DUMP_EXT ), '', basename( $file ) );
                                                $file    = '<strong>' . sl_get_page_name( $page_id ) . '</strong>';
                                                $pages[] = array( $file );
                                            }
                                            else
                                            {
                                                // this is a file
                                                $files[] = array( $file );
                                            }
                                        }

                                        // show pages list
                                        if ( !empty( $pages ) )
                                        {
                                            $this->table->headings( array( 'Pages Submitted' ) );
                                            $this->table->data( $pages );
                                            echo wp_kses( $this->table->build_table(), $table_rules );
                                            echo '<p><br /></p>';
                                        }

                                        // files list
                                        $this->table2->headings( array( 'Files Submitted' ) );
                                        $this->table2->data( $files );
                                        ?>
                                        <div style="width: 100%; height: 500px; overflow: auto;">
                                            <?php echo wp_kses( $this->table2->build_table(), $table_rules ); ?>
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            else
                            {
                                if ( isset( $current_scan ) )
                                {
                                    if ( !empty( $current_scan[ 'message' ][ 'files' ] ) && is_array( $current_scan[ 'message' ][ 'files' ] ) )
                                    {
                                        $pages = $files = array();
                                        
                                        // organize all files into pages and files
                                        foreach ( $current_scan[ 'message' ][ 'files' ] as $file )
                                        {
                                            if ( strpos( $file, 'slwp_page_' ) !== false )
                                            {
                                                // this is a specific page
                                                $page_id = (int) str_replace( array( 'slwp_page_', '.txt' ), '', basename( $file ) );
                                                $file    = '<strong>' . sl_get_page_name( $page_id ) . '</strong>';
                                                $pages[] = array( $file );
                                            }
                                            else
                                            {
                                                // this is a file
                                                $files[] = array( $file );
                                            }
                                        }

                                        // show pages list
                                        if ( !empty( $pages ) )
                                        {
                                            $this->table->headings( array( 'Infected Pages' ) );
                                            $this->table->data( $pages );
                                            echo wp_kses( $this->table->build_table(), $table_rules );
                                            echo '<p><br /></p>';
                                        }

                                        // files list
                                        $this->table2->headings( array( 'Infected Files' ) );
                                        $this->table2->data( $files );
                                        echo wp_kses( $this->table2->build_table(), $table_rules );
                                    }
                                }
                                
                                ?>
                                <p>Initiate a smart scan to find malware in your source code.  The files associated to your plugins and themes will be encrypted, zipped, and sent to our secure scanning servers for evaluation.</p>
                                <form method="post">
                                    <?php echo get_submit_button( 'Submit New Source Code Scan', 'primary', 'sl_submit_scan', true, array( 'id' => 'sl_submit_scan' ) ); ?>
                                </form>
                                <?php
                            }
                        ?>
                        
                    </div><!-- /.span12 -->
                <?php } ?>
                
                <?php if ( $this->can_fix ) { ?>
                <div class="span6">
                    
                    <div class="wpslp_summary_box">
                        <h2>Full Source Code Scan Configuration</h2><!-- <br /> -->
                    </div>
                    
                    <?php if ( isset( $success_save_ftp ) ) { ?>
                        <div id="message" class="success notice notice-success below-h2">
                            <p>
                                Settings saved.
                            </p>
                        </div>
                    <?php } ?>
                    
                    <p>Ready to run a full scan of all source code at one time? Configure your FTP credentials so we can pull source code direct form your server and provide a deep scan to find malware at the source. <a target="_blank" href="<?php echo esc_url_raw( sitelock_sso( 'smart' ) ); ?>">View SMART Scan Details on SiteLock Dashboard</a></p>
                    
                    
                    
                    <?php
                     
                        if ( !empty( $this->boxes ) ) 
                        {
                            foreach ( $this->boxes as $order => $data ) 
                            {
                                if ( strpos( $order, 'smart' ) !== false )
                                {
                                    if ( $data[ 'details' ] != '' )
                                    {   
                                        ?>
                                        <div id="message" class="error notice notice-error is-dismissible below-h2">
                                            <p>
                                                <?php echo esc_html( $data[ 'details' ] ); ?>
                                            </p>
                                            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                                        </div>
                                        <?php
                                    }
                                    
                                    continue;
                                }
                            }
                        }
                        
                    ?>
                    
                    
                    <form method="post" autocomplete="off">

                        <div class="row">
                            <div class="span4">
                                <label>Method for File Transfers</label>
                            </div>
                            <div class="span8">
                                <select name="ftp_type" class="sl_select_width_100">
                                    <?php
                                        
                                        $array = array( 'ftp', 'ftps' );
                                        
                                        foreach ( $array as $method )
                                        {
                                            $current = ( isset( $smart_settings[ 'protocol' ] ) && $smart_settings[ 'protocol' ] == $method ? ' selected' : '' );
                                            ?><option value="<?php echo esc_attr( $method ); ?>"<?php echo esc_attr( $current ); ?>><?php echo esc_attr( $method ); ?></option><?php
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>FTP Host Address</label>
                            </div>
                            <div class="span8">
                                <input type="text" name="ftp_host" class="sl_select_width_100" value="<?php echo ( isset( $smart_settings[ 'ftp_host' ] ) ? esc_attr( $smart_settings[ 'ftp_host' ] ) : '' ); ?>" />
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>Port Number</label>
                            </div>
                            <div class="span8">
                                <input type="text" name="ftp_port" class="sl_select_width_100" value="<?php echo ( isset( $smart_settings[ 'port' ] ) ? esc_attr( $smart_settings[ 'port' ] ) : '' ); ?>" />
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>Root Directory</label>
                            </div>
                            <div class="span8">
                                <input type="text" name="ftp_root" class="sl_select_width_100" value="<?php echo ( isset( $smart_settings[ 'docroot' ] ) ? esc_attr( $smart_settings[ 'docroot' ] ) : get_home_path() ); ?>" />
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>User</label>
                            </div>
                            <div class="span8">
                                <input type="text" name="ftp_user" class="sl_select_width_100" value="<?php echo ( isset( $smart_settings[ 'ftp_user' ] ) ? esc_attr( $smart_settings[ 'ftp_user' ] ) : '' ); ?>" autocomplete="off" />
                            </div>
                        </div>
                        <br />
                        
                        <!-- originally used to remove autofill but does not work anymore, thank you chrome -->
                        <div style="display: none;">
                            <input type="text" name="text2" />
                            <input type="password" name="password" />
                            <input type="text" name="text" />
                        </div>
                        
                        <div class="row">
                            <div class="span4">
                                <label>Password</label>
                            </div>
                            <div class="span8">
                                <input type="password" name="ftp_pass" placeholder="<?php echo ( isset( $smart_settings[ 'ftp_user' ] ) ? 'Password saved on file, enter new password to change' : '' ); ?>" class="sl_select_width_100" value="" autocomplete="off" />
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>File Download Speed</label>
                            </div>
                            <div class="span8">
                                <select name="ftp_download_speed" class="sl_select_width_100">
                                    <?php
                                        
                                        $array = array( '1' => 'Normal (1 connection)', '2' => 'Faster (2 simultaneous connections)', '3' => 'Fastest (3 simultaneous connections)' );
                                        
                                        foreach ( $array as $key => $val )
                                        {
                                            $current = ( isset( $smart_settings[ 'parallel' ] ) && $smart_settings[ 'parallel' ] == $key ? ' selected' : '' );
                                            ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></option><?php
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <br />
                        
                        <div class="row">
                            <div class="span4">
                                <label>Maximum Download Time</label>
                            </div>
                            <div class="span8">
                                <select name="ftp_download_time" class="sl_select_width_100">
                                    <?php
                                        
                                        $array = array( '1800' => '30 minutes / day', '3600' => '60 minutes / day', '5400' => '90 minutes / day', '7200' => '120 minutes / day' );
                                        
                                        foreach ( $array as $key => $val )
                                        {
                                            $current = ( isset( $smart_settings[ 'ftp_timeout' ] ) && $smart_settings[ 'ftp_timeout' ] == $key ? ' selected' : '' );
                                            ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></option><?php
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <br />
                        
                        <?php echo get_submit_button( 'Save', 'primary', 'sl_save_ftp', true, array( 'id' => 'sl_save_ftp' ) ); ?>
                        
                    </form>
                    
                </div>
                <?php } ?>
                
                <div class="clearfix"></div>
                
            </div><!-- /.row -->
        </div><!-- /.wpslp_container -->
    </div><!-- /.sitelock -->
</div><!-- /ie check -->
</div><!-- /.wrap -->
