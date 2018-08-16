<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

use Cake\Mailer\Email;
/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);
        $this->loadComponent('Flash');
        //$this->loadComponent('Security');
        //$this->loadComponent('Csrf');
    }

    function _sendMail( $to, $subject, $template, $data = array() ) {
        $this->Email = new Email();
        $this->Email->setTransport('ssl');
        $result = $this->Email->setTo( $to )
                              ->setSubject( $subject )
                              ->setViewVars( $data )
                              ->setTemplate( $template )
                              ->setEmailFormat( 'html' ) //Send as 'html', 'text' or 'both' (default is 'text')
                              ->send();
        if( isset( $result['headers'] ) ){
          return true;
        }
        return false;
        // $this->Email->bcc = array('secret@example.com'); // copies
        // $this->Email->replyTo = 'noreply@domain.com';
    }
}
