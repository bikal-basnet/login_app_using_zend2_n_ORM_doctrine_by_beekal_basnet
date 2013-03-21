<?php
/// <summary>
///  This file contains the ORM entity defination for login Information. Also contains validation, associatation settings between user and Login Information. 
/// </summary>
/// Created : 2013 March 20, Bikal Basnet


namespace User\Entity;
 
use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use User\Entity\User;

/**
 * .
 *
 * @ORM\Entity
 * @ORM\Table(name="login_information")
 */
class LoginInformation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint");
     * @ORM\GeneratedValue(strategy="AUTO")
     */
	private $id;
	
	// ...
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="login_informations",cascade="persist")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;
	
	 
	 /**
     * @ORM\Column(type="string", length=50, nullable=false);
     */
	public $logged_in_date_time;
	
	
	public function __construct(){
		//$this->logged_in_date_time = new \DateTime(date("Y-m-d H:i:s"));
		$this->logged_in_date_time = time();
	}
	
	public function setUser(User $user = null) {
        $this->user = $user;
    }
    // ...
	/**
     * Populate from an array.
     *
     * @param array $data
     */
    public function populate($data = array()) 
    {
//        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->logged_in_date_time = $data['logged_in_date_time'];
    }
	
	/**
     * Magic getter to expose protected properties.
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property) 
    {
        return $this->$property;
    }
 
    /**
     * Magic setter to save protected properties.
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value) 
    {
        $this->$property = $value;
    }
 
}

?>