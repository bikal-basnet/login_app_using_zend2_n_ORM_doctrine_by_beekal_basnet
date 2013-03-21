<?php
// Summary : This file  is the user controller.  
// Updated : March -18 -2013 Bikal Basnet, 
//					indexAction : display sign in form and sign up link
//					signupAction : display sign up form. sign up user when they provide  information
//
//			March-20,Bikal Basnet
//					signinAction : handles the user  sign in event. Using ORM object associatation and mapping to record login details
//					profileAction : display the user with the last 5 logged in date and time
//					logoutAction  : logs out the user and redirect to home page					
					
//

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
//use User\Form\UserForm;
use User\Form\UserSignUpForm;
use User\Form\UserSignInForm;
use Doctrine\ORM\EntityManager;
use User\Entity\User;
use Zend\View\Helper\AbstractHelper; 
use User\Entity\LoginInformation;


class UserController extends AbstractActionController
{
	// doctrine ORM entity manager
	protected $em;
	
		/// <summary>
		/// set the ORM entity manager.  			
		/// </summary>
        /// <param> $em : entity manager</param>
		/// <returns></returns>
		
	public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
	
		/// <summary>
		/// get entity manager.
		/// if entity manager is not already set then loads the entity manager from service provider(as defined in module.config)
		/// else return the predefined entity manager  			
		/// </summary>
        /// <param></param>
		/// <returns></returns>
		
	public function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }
        return $this->em;
    } 

		/// <summary>
		/// indexAction : displays  sign in form and sign up link
		/// </summary>
        /// <param></param>
		/// <returns></returns>

	public function indexAction()
    {
		// instantiate sign in form as defined in Form/SignInForm
        $form = new UserSignInForm();
		
		// get the submit button and give it "Log in" name  
        $form->get('submit')->setAttribute('value', 'Log In');
 		
		// pass form to view, which is then rendered there
        return array('form' => $form);
	
    }


		/// <summary>
		/// signupAction : displays  sign up form and  when the information is passed, create the user account
		/// </summary>
        /// <param></param>
		/// <returns></returns>
		
	public function signupAction()
    {
        $form = new UserSignUpForm();
        $form->get('submit')->setAttribute('value', 'Sign Up');

	    $request = $this->getRequest();
		// check if the data has been posted back . 
		// if data posted back, then check for data validity and enter the user to db or display error message
        if ($request->isPost()) {

			// validate the data against the constraints as defined in the user class, hence instantiate user
            $new_user = new User();           
			// bind the data filters defined in user class to the form            
            $form->setInputFilter($new_user->getInputFilter());
			// bind the data supplied from the user's  'sign up' form submission, to the form 
            $form->setData($request->getPost());
			
			// check if the user provided data satisfies the  constraint specified in user class or not
            if ($form->isValid()) { 			
            	// data is valid, insert user to db
				
				// load the user provided data to the  user object
			    $new_user->populate($form->getData()); 
				//$email = $this->getRequest()->getPost('email');
				
				//check if the user with the email, has already registered or not				
				if($new_user->hasEmailPreRegistered($this->getEntityManager())){
					//display message to the user, that email has alredy been registered.					
					
					// flashmessenger is zend standard, used to pass messages between actions					
					$this->flashMessenger()->setNamespace('error')->addMessage('The user with the email already exist.');
			        $this->redirect()->toRoute('user',array('action'=>'signup'));
				}else{
					// user is new user, insert the user to db
					
					$this->getEntityManager()->persist($new_user);
					// performs query operation and synchronises the objects with the database 
					$this->getEntityManager()->flush();
	 
					// display user sucessfully registered message and redirect to home page
					$this->flashMessenger()->setNamespace('success')->addMessage('Sucess : You have  signed up. Please use your email and password to sign in');
					return $this->redirect()->toRoute('user'); 		
				}				
            }
        }
 
        return array('form' => $form);
    } 
	
	
	/// <summary>
		/// signinAction : handles user sign in
		/// </summary>
        /// <param></param>
		/// <returns></returns>
	
	public function signinAction()
    {				
		// bind the sign in data, posted back to the user's constraint
        $sign_in_form = new UserSignInForm();
        $new_user = new User();    
		$sign_in_form->setInputFilter($new_user->getInputFilter());
		$sign_in_form->setData($this->getRequest()->getPost());
		
		// server side data validation : for email and password only, since only email and password would be provided from sign in form. only perform db query if data satisfies server side data validation 
		$sign_in_form->setValidationGroup('email', 'password');
        if ($sign_in_form->isValid()) { 				
			// data passes server validation.  check for authentication across db now,
				
				$new_user->populate($this->getRequest()->getPost());				
				// set the repository in the user object
				$new_user->setRepository($this->getEntityManager());								
				// check if user with email and password  has account or not
				// if user exists, then  authentic user object is obtained
				$authenticated_user_obj = $new_user->authenticate();			
				if (!$new_user->isAuthentic()) {
					// user  do not have account with us. redirect to home page and display message
					
	 				$this->flashMessenger()->setNamespace('error')->addMessage('Error : The user with the email and the password does not exist. Please try again');
					return $this->redirect()->toRoute('user'); 		
				} else {		
					// user  has account with us, add the sign in datetime in db
					
					// associate the login information object to the  user object
					$authenticated_user_obj->addLoginInformation();
					$this->getEntityManager()->persist($authenticated_user_obj);

					// add the object, along with its  one to many relationship and other relationships to the database 
					$this->getEntityManager()->flush();		
			        return $this->redirect()->toRoute('user', array('controller'=>'user', 'action'=>'profile'));
					exit;
				}
	      }else{
			  // user provided email and password did not pass the server side constraint check, redirect to home page and display message
			  
			  return array('form' => $sign_in_form);
		  	$this->flashMessenger()->setNamespace('error')->addMessage('Error : Invalid username or Password. Please try again');
			return $this->redirect()->toRoute('user'); 
		  }	  
     }
	 
 		 /// <summary>
		/// profileAction : extracts the last 5 sign in datetime and displays. used Doctrine Query Language
		/// </summary>
        /// <param></param>
		/// <returns></returns>
	
	/*profile action using Doctrine Query Language*/
    public function profileAction()
    {
		// check if user has signed in or not. if not signed in redirect to home page
		if(!User::isLoggedIn()){
			return $this->redirect()->toRoute('user');
			exit;
		}
		// generate dql query  to extract the last 5 login time and day
		$query = $this->getEntityManager()->createQuery("select l from User\Entity\LoginInformation l where l.user = ".$_SESSION['user_id']." order by l.logged_in_date_time desc")->setMaxResults(5);
		$login_informations = $query->getResult();    
		
		//pass the login information object to the view, where it is displayed properly 
        return array(
            'login_informations' => $login_informations
        );
    }
	
	
		 /// <summary>
		/// logoutAction : log out the user from the system and redirect to home page
		/// </summary>
        /// <param></param>
		/// <returns></returns>
		
    public function logoutAction()
    {
		User::LogOut();
		return $this->redirect()->toRoute('user'); 		
	}
}

?>