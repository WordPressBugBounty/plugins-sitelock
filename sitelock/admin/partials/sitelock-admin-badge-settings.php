<?php

/**
 * Badge Settings
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */
?>

<div class="sitelock">



<?php 

    if ( !empty( $this->status ) ) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible below-h2"><p><?php echo esc_html( $this->status ); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
        <?php
    }
    
?>

<div class="wrap wpslp_container"><div id="icon-tools" class="icon32"></div>
<h2>My SiteLock Badge Settings</h2>

<p>Badge settings are only available for the domain you are currently working from inside WordPress.</p>

<?php // @todo change site_url() to $this->sl_site_url(); ?>
<p>Your current domain is: <a href="<?php echo esc_url_raw( site_url() ); ?>" target="_blank"><?php echo esc_url_raw( site_url() ); ?></a></p>

<form method="post">
    
    <div class="row">
        <div class="span6">
            <div class="postbox metabox-holder">
                <h3><span>Settings</span></h3>
                <div class="inside">
                
                    <div class="form-group">
                        <div class="row">
                            <div class="span4">
                                <label>Size</label>
                            </div><!-- /span4 -->
                            <div class="span8">
                                <select name="sitelock_badge_size" class="form-control">
                                    <?php
                                        $options = array(
                                            'small'  => 'Small',
                                            'medium' => 'Medium',
                                            'big'  => 'Big'
                                        );
                                        $rules = array(
                                            'option' => array( 'value' => array(), 'selected' => array() )
                                        );
                                        echo wp_kses( sitelock_array_to_options( $options, $this->current_badge_size ), $rules );
                                    ?>
                                </select>
                            </div><!-- /span8 -->
                        </div><!-- /row -->
                    </div><!-- /form-group -->
                
                    <div class="form-group">
                        <div class="row">
                            <div class="span4">
                                <label>Color</label>
                            </div><!-- /span4 -->
                            <div class="span8">
                                <select name="sitelock_badge_color" class="form-control">
                                    <?php
                                        $options = array(
                                            'red'  => 'Red',
                                            'white' => 'White'
                                        );
                                        echo wp_kses( sitelock_array_to_options( $options, $this->current_badge_color ), $rules );
                                    ?>
                                </select>
                            </div><!-- /span8 -->
                        </div><!-- /row -->
                    </div><!-- /form-group -->
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="span4">
                                <label>Type</label>
                            </div><!-- /span4 -->
                            <div class="span8">
                                <select name="sitelock_badge_type" class="form-control">
                                    <?php
                                        $options = array(
                                            'malware-free'  => 'Malware Free',
                                            'secure'        => 'Secure'
                                        );
                                        echo wp_kses( sitelock_array_to_options( $options, $this->current_badge_type ), $rules );
                                    ?>
                                </select>
                            </div><!-- /span8 -->
                        </div><!-- /row -->
                    </div><!-- /form-group -->
                    
                </div><!-- /inside -->
            </div><!-- /postbox -->
        </div><!-- /span6 -->
        
        <div class="span6">
            
            <div class="postbox metabox-holder">
                <h3><span>Location on page</span></h3>
                <div class="inside">
                    
                    <div class="row">
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute top left" type="radio" name="sitelock_badge_location" value="0-0"<?php echo esc_html( $this->current_badge_location == '0-0' ? ' checked' : '' ); ?> />
                            <br />
                            Top Left<br /><br />
                        </label>
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute top h-mid" style="margin-right: -5px;" type="radio" name="sitelock_badge_location" value="0-50"<?php echo esc_html( $this->current_badge_location == '0-50' ? ' checked' : '' ); ?> />
                            <br />
                            Top Center<br /><br />
                        </label>
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute top right" type="radio" name="sitelock_badge_location" value="0-100"<?php echo esc_html( $this->current_badge_location == '0-100' ? ' checked' : '' ); ?> />
                            <br />
                            Top Right<br /><br />
                        </label>
                    </div><!-- /row -->
                    
                    <div class="row">
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute bottom left" type="radio" name="sitelock_badge_location" value="100-0"<?php echo esc_html( $this->current_badge_location == '100-0' ? ' checked' : '' ); ?> />
                            
                            Bottom Left
                            <br /><br /><br />
                        </label>
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute bottom h-mid" style="margin-right: -5px;" type="radio" name="sitelock_badge_location" value="100-50"<?php echo esc_html( $this->current_badge_location == '100-50' ? ' checked' : '' ); ?> />
                            
                            Bottom Center
                            <br /><br /><br />
                        </label>
                        <label class="span4 text-center gray-bg relative">
                            <br />
                            <input class="absolute bottom right" type="radio" name="sitelock_badge_location" value="100-100"<?php echo esc_html( $this->current_badge_location == '100-100' ? ' checked' : '' ); ?> />
                            
                            Bottom Right
                            <br /><br /><br />
                        </label>
                    </div><!-- /row -->
                    
                    <p>
                        <label>
                            <input type="radio" name="sitelock_badge_location" value="hide"<?php echo esc_html( $this->current_badge_location == '0' || $this->current_badge_location == 'hide' ? ' checked' : '' ); ?> /> Hide Badge
                        </label>
                    </p>
                
                </div><!-- /inside -->
            </div><!-- /postbox -->
            
        </div><!-- /span6 -->
    </div><!-- /row -->
    
    <?php submit_button( 'Save' ); ?>
                
</form>

</div>
