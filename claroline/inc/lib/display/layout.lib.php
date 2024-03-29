<?php // $Id$

/**
 * Claroline Layout library
 *
 * @version     Claroline 1.9 $Revision$
 * @copyright   2001-2009 Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     kernel.display
 * @author      Frederic Minne <frederic.minne@uclouvain.be>
 */

interface Layout extends Display {};

abstract class TwoColumnsLayout implements Layout
{
    protected $left = '';
    protected $right = '';
    
    public function prependToLeft( $str )
    {
        $this->left = $str . $this->left;
    }
    
    public function prependToRight( $str )
    {
        $this->right = $str . $this->right;
    }
    
    public function appendToLeft( $str )
    {
        $this->left .= $str;
    }
    
    public function appendToRight( $str )
    {
        $this->right .= $str;
    }
    
    abstract public function renderLeft();
    
    abstract public function renderRight();
    
    public function render()
    {
        return $this->renderLeft() . $this->renderRight()
            . '<div class="spacer"></div>' . "\n"
            ;
    }
}

class LeftMenuLayout extends TwoColumnsLayout
{
    public function renderLeft()
    {
        return '<div id="leftSidebar">' . "\n" . $this->left . '</div>' . "\n";
    }
    
    public function renderRight()
    {
        return '<div id="rightContent">' . "\n" . $this->right . '</div>' . "\n";
    }
}

class RightMenuLayout extends TwoColumnsLayout
{
    public function renderLeft()
    {
        return '<div id="leftContent">' . "\n" . $this->left . '</div>' . "\n";
    }
    
    public function renderRight()
    {
        return '<div id="rightSidebar">' . "\n" . $this->right . '</div>' . "\n";
    }
}
