<?phpnamespace Zf2EmailService\Service;use \Zend\Mail\Message as Message;use \Zend\Mime\Part as MimePart;use \Zend\Mime\Message as MimeMessage;use \Zend\Mail\Transport\Sendmail as SendmailTransport;use \Zend\View\Model\ViewModel;use \Zend\ServiceManager\ServiceLocatorAwareInterface;use \Zend\ServiceManager\ServiceLocatorInterface;class Email extends Message implements ServiceLocatorAwareInterface{    protected $template;    protected $layout;    protected $templateVariables;    protected $layoutVariables;    protected $isReady = false;    /**     * @var ServiceLocatorInterface     */    protected $serviceLocator;    /**     * @return ServiceLocatorInterface     */    public function getServiceLocator()    {        return $this->serviceLocator;    }    /**     * @param ServiceLocatorInterface $serviceLocator     */    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)    {        $this->serviceLocator = $serviceLocator;    }    public function setTemplate($template, $variables = array())    {        $this->template = $template;        $this->templateVariables = $variables;        return $this;    }    /**     * @param $template     * @param array $variables     * @return $this     */    public function setLayout($template, $variables = array())    {        $this->layout = $template;        $this->layoutVariables = $variables;        return $this;    }    /**     *     */    public function reset()    {        $this->isReady = false;    }    /**     * @throws \Exception     */    public function buildBody()    {        if (!$this->isReady) {            $render = $this->serviceLocator->get('ViewRenderer');            try {                $view = new ViewModel();                if (!empty($this->templateVariables)) {                    $view->setVariables($this->templateVariables);                }                $body = $render->render($view->setTemplate($this->template));                if (!empty($this->layout)) {                    $view = new ViewModel();                    $this->layoutVariables['content'] = $body;                    $view->setVariables($this->layoutVariables);                    $body = $render->render($view->setTemplate($this->layout));                }                $htmlPart = new MimePart($body);                $htmlPart->type = "text/html";                $textPart = new MimePart('text');                $textPart->type = "text/plain";                $body = new MimeMessage();                $body->setParts(array($textPart, $htmlPart));                $this->setEncoding("UTF-8");                $this->setBody($body);                $this->getHeaders()->get('content-type')->setType('multipart/alternative');            } catch (\Exception $e) {                throw new \Exception("Can't find template " . $this->template . '.phtml' . $e->getMessage());            }        }    }    /**     * @return mixed     */    public function getSubject()    {        if (!$this->isReady) {            try {                /** @var \Zend\View\Model\ViewModel $view */                $view = new ViewModel();                $render = $this->serviceLocator->get('ViewRenderer');                $subject = $render->render($view->setTemplate($this->template . '.subj'));                $this->setSubject($subject);            } catch (\Exception $e) {                $this->setSubject('');            }        }        return parent::getSubject();    }    /**     * @return bool     */    public function sendSmtp()    {        try {            $config = $this->serviceLocator->get('Config');            $this->buildBody();            $this->getSubject();            $transport = isset($config['smtp']) ? $this->serviceLocator->get(                'Zend\Mail\Transport\Smtp'            ) : new SendmailTransport();            // Send forgot password message            $transport->send($this);            return true;        } catch (\Exception $e) {            return false;        }    }}