<?php

/**
 * The class is used for building tables
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin
 * @author     Todd Low <tlow@sitelock.com>
 */
class Sitelock_Table {
    
    /**
     * Table headings
     *
     * @since 2.0.0
     */
    private $headings;
    
    
    /**
     * Table footings
     *
     * @since 2.0.0
     */
    private $footings;
    
    
    /**
     * Table data
     *
     * @since 2.0.0
     */
    private $data;
    
    
    /**
     * Initiates the table
     *
     * @since 2.0.0
     */
    public function open_table()
    {
        return '<table class="widefat fixed" cellspacing="0">';
    }
    
    
    /**
     * Initiates the table
     *
     * @since 2.0.0
     */
    public function close_table()
    {
        return '</table>';
    }
    
    
    /**
     * Builds the table header and footer
     *
     * @since 2.0.0
     * @param array $headings Array of headings
     */
    public function headings( $headings )
    {
        foreach ( $headings as $heading )
        {
            $heading_slug = sanitize_title( $heading );
            
            $this->headings .= '<th id="sl_' . $heading_slug . '" class="manage-column column-columnname" scope="col">' . $heading . '</th>';
            $this->footings .= '<th class="manage-column column-columnname" scope="col">' . $heading . '</th>';
        }
        
        $this->headings = '<thead>' . $this->headings . '</thead>';
        $this->footings = '<tfoot>' . $this->footings . '</tfoot>';
    }
    
    
    /**
     * Builds the table data
     *
     * @since 2.0.0
     * @param array $data Array of data
     */
    public function data( $table_data )
    {
        $alt = '';
    
        foreach ( $table_data as $data )
        {
            $alt = ( $alt == '' ? 'alternate' : '' );
            
            $this->data .= '<tr class="' . $alt . '">';
            
            foreach ( $data as $column )
            {
                $this->data .= '<td class="column-columnname">' . $column . '</td>';
            }
            
            $this->data .= '</tr>';
        }
    }
    
    
    /**
     * Builds the entire table
     *
     * @since 2.0.0
     * @param boolean $show_headings If set to false the headings will not be displayed
     */
    public function build_table( $show_headings = true )
    {
        return $this->open_table() . ( $show_headings ? $this->headings : '' ) . $this->data . ( $show_headings ? $this->footings : '' ) . $this->close_table();
    }



}