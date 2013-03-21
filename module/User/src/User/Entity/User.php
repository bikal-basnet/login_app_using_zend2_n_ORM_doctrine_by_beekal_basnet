<?php
/// <summary>
///  This file contains the ORM entity defination, validation, associatation settings between user and Login Information and also handles activities related with  user 
/// </summary>
/// Created : 2013 March 18, Bikal Basnet
/// Updated : 2013 March 20, Bikal Basnet 

namespace User\Entity;
 
use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 
use User\Entity\LoginInformation;
 
/**
 * A user.
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
 * @property string $email
 * @property string $password
 * @property int $id
 */
class User implements InputFilterAwareInterface 
{
	// input filter defines contraint for the entities used for validation and for sanitisation before db insertion and desanitisation before db extraction.
    protected $inputFilter;
 
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint");
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
 
    /**
     * @ORM\Column(type="string",length=30)
     */
    protected $email;
 
    /**
     * @ORM\Column(type="string",length=50)
     */
    protected $password;
	
	 // dummy property
    private $confirm_password;
	
	
	// ...one to many associatation. one user has many loginInformation
    /**
     * @ORM\OneToMany(targetEntity="LoginInformation", mappedBy="user", cascade="persist")
     **/ 
    private $login_informations;
    // ...

	// construct : 	login_informations set to array collection. to satisfy one to many relationship. 1 user can contain many login_information
    public function __construct() {
        $this->login_informations = new \Doctrine\Common\Collections\ArrayCollection();
    }
	
	
	public function getLoginInformations() { return $this->login_informations; }
	public function getLoggedInDetails() { return $this->getLoginInformations(); }

	// constants used  to define exception message
	const ENTITY_MANAGER_NULL_EXC = "Entity Manager is not provided. Please set Entity Manager, before proceeding.";
	const EMAIL_NULL_EXC = "No email provided. Empty should be set, before proceeding.";
	const PASSWORD_NULL_EXC = "Invalid : Empty password. Password must be set before proceeding";
	
	
	// user repository
    private $_user_repository = "";
	// is user authentic with the database. does user has account
	private $_is_authentic =  false;
	
	// entity manager
	private $_entity_manager = "";
	
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
 
    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function getArrayCopy() 
    {
        return get_object_vars($this);
    }
 
    /**
     * Populate from an array.
     *
     * @param array $data
     */
    public function populate($data = array()) 
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = md5($data['password']);
		$this->confirm_password = md5($data['confirm_password']);
    }
	
	/*
	*	<summary>checks if the user with the email has laready registered  or not</summary>
	*   params $entity_manager :  if entity manager is provided then it is used, rather than the predescribed one
	*	<returns> true if  user  has already registered with the email address,
	*				false otherwise			
	*	</returns>
	*/
	public function hasEmailPreRegistered($_entity_manager = "" ){
		$this->setRepository($_entity_manager);
		$this->raiseExceptionOnEmptyEmail();
		// get the users with  the email from the database
		$user_if_in_db = $this->_user_repository->findBy(array(
                "email"    => $this->email));
		// if number of users with email is  greater than zero then return true		
		if(count($user_if_in_db) > 0)
			return true;
		return false;
	}
    
	/**
     * <summary>authenticate user by email and password.</summary>     *
     *  <returns> authenticated user object if user has account ,
	 		false otherwise </returns>
     */
    public function authenticate() 
    {		
		$this->raiseExceptionOnEmptyEmail();
		$this->raiseExceptionOnEmptyPassword();		
        $user_frm_db = $this->getRepository()->findBy(array("email"=>$this->email,"password"=>$this->password));
//		$this->id = $user_frm_db[0]->id ;
		if (count($user_frm_db) > 0 ) {
			// if user is authentic then register the user in session to maintain the state
			
			$this->registerUserInSession($user_frm_db[0]);
			$this->_is_authentic = true; 
			return $user_frm_db[0];
		}else{
			$this->_is_authentic = false; 
			return false;
		}
    }
	
	/**
     * <summary>one to many associatation management.
	 *	Keeps the persistance correct. 
	 *	hide persistance maintainance complexity with simplied function</summary>     *
     *  <returns></returns>
     */
	
	public function addLoginInformation(){
		$new_login_information = new LoginInformation();		
		$this->getLoginInformations()->add($new_login_information);
		$new_login_information->setUser($this);		
	}
	
	
	/**
     * <summary>add the login details to the login Information table</summary>     *
	 * <param>user id</param>
     *  <returns>/returns>
     */
	/*private function addLoginInformationToDb($user_id){
		
		$new_login_information = new LoginInformation();		
		$this->getLoginInformations()->add($new_login_information);
		$new_login_information->setUser($this);
		$this->_entity_manager->remove($this);
		$this->_entity_manager->persist($new_login_information);
		$this->_entity_manager->flush();
	}
	*/
	
	/**
     * <summary>registers the user in the session variable</summary>     *
	 * <param>object user  records i.e $user->id=1, $user->email"="abc@abc.com"</param>
     *  <returns>/returns>
     */
	private function registerUserInSession($user_obj){
		session_start();
		$_SESSION['user_id'] =  $user_obj->id;	
	}
	
	/**
     * <summary>checks if the user is logged in or not</summary>     *
	 * <param></param>
     *  <returns>true if user is logged in, false otherwiise</returns>
     */
	public static function isLoggedIn(){	
		// need to start session, before checking its contents
		// however if session pre set, then session start cannot be invoked twice, hence we check for session and start only if not preset.
		if(!session_id()) session_start();			
		if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != "" )
			return true;
		return false;
		
	}
	
	/**
     * <summary>log our the user from the system</summary>     *
	 * <param></param>
     *  <returns></returns>
     */
	
	public static function LogOut(){
		session_start();		
		session_destroy();	
		$_SESSION = array();		
	}
 
	/*
	*	<summary>accepts entity manager and then creates and sets user repository   from it</summary>
	* 	<param>entity_manager :  revieves the entity manager </param>
    *  <returns></returns>
	*/
	public function setRepository($_entity_manager){
		//if user repository not pre set, set the repository 
		if($this->_user_repository == "" ){
			if($_entity_manager == ""){
				throw new \Exception(self::ENTITY_MANAGER_NULL_EXC);
			}else{
				$this->_entity_manager = $_entity_manager;
				$this->_user_repository = $_entity_manager->getRepository("User\Entity\User"); 
			}
		}
	
	}
	
	/*
	*	<summary>get repository</summary>
	*/
	private function getRepository(){
		if($this->_user_repository == "")
			throw new \Exception(self::REPOSITORY_NULL_EXC);		
		return $this->_user_repository; 
	
	}
	
	/*
	*	<summary>if object has empty email then raise exception</summary>
    *  <returns></returns>
	*/
	private function raiseExceptionOnEmptyEmail(){ 		
		if($this->email == "")
			throw new Exception(self::EMAIL_NULL_EXC);
 	}
	
	private function raiseExceptionOnEmptyPassword(){ 		
		if($this->password == "")
			throw new Exception(self::PASSWORD_NULL_EXC);
 	}

	/*
	*	<summary>gives if user is authentic or not i.e if user has account or not</summary>
    *  <returns>return user authentication result</returns>
	*/
	
	public function isAuthentic(){
		return $this->_is_authentic;
	}	
	
	
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
 
 
	/*
	*	<summary>define entity constraints, filters to be applied before user insertion and user extraction here.
	*	used for server side validation, database entity constraint </summary>
    *  <returns></returns>
	*/
	
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
 
            $factory = new InputFactory();
 
            $inputFilter->add($factory->createInput(array(
                'name'       => 'id',
                'required'   => true,
                'filters' => array(
                    array('name'    => 'Int'),
                ),
            )));
 
            $inputFilter->add($factory->createInput(array(
                'name'     => 'email',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                      'name' =>'NotEmpty', 
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter Email!' 
                            ),
                        ),
                    ),					
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 6,
                            'max'      => 30,
							'messages' => array(
                                'stringLengthTooShort' => 'Please enter Email between 6 to 30 character!', 
                                'stringLengthTooLong' => 'Please enter Email between 6 to 30 character!' 
                            ),
                        ),						
                    ),
					array(
                      'name' =>'EmailAddress', 
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\EmailAddress::INVALID_FORMAT => 'Email address format is invalid!' 
                            ),
                        ),
                    ),
                ),
            )));
 
            $inputFilter->add($factory->createInput(array(
                'name'     => 'password',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                      'name' =>'NotEmpty', 
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter Password!' 
                            ),
                        ),
                    ),
					array(
                        'name'    => 'StringLength',
						'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 6,
                            'max'      => 30,
							'messages' => array(
                                'stringLengthTooShort' => 'Please enter Password between 6 to 30 character!', 
                                'stringLengthTooLong' => 'Please enter Password between 6 to 30 character!' 
                            ),
                        ),
                    ),
                ),
            )));
			
            $inputFilter->add($factory->createInput(array(
                'name'     => 'confirm_password',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                      'name' =>'NotEmpty', 
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter Confirm Password!' 
                            ),
                        ),
                    ),

                    array(
                      'name' =>'Identical', 
                        'options' => array(
							'token' => "password",
                            'messages' => array(
								\Zend\Validator\Identical::NOT_SAME => 'Password and the confirm password field do not match!',
								 
                            ),
                        ),
                    ),
		        ),
            )));

 
            $this->inputFilter = $inputFilter;        
        }
 
        return $this->inputFilter;
    }
}

?>