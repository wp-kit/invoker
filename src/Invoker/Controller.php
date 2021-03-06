<?php
    
    namespace WPKit\Invoker;
    
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Support\Facades\Request;
    
    class Controller {
	    
	    /**
	     * @var Illuminate\Contracts\Container\Container
	     */
	    protected $app = null;
        
        /**
	     * @var array
	     */
        protected $scripts = [];
	    
	    /**
	     * @var string
	     */
	    protected $scriptsAction = 'wp_enqueue_scripts';
	    
	    /**
	     * @var string
	     */
	    protected $scriptsPriority = 10;
        
        /**
        
        /**
	     * Controller constructor
	     *
	     * @param  \Illuminate\Contracts\Container\Container  $app
	     * @return void
	     */
        public function __construct(Container $app) {
	        
	        $this->app = $app;
	        
        }
        
        /**
	     * Register method called before controller is booted
	     *
	     * @return void
	     */
        public function register(Request $request) {
		
			// backward compatibility
			$this->scriptsAction = property_exists($this, 'scripts_action') ? $this->scripts_action : $this->scriptsAction;
		
			add_action( $this->scriptsAction, [$this, 'enqueueScripts'], $this->scriptsPriority );
	        
        }
		
		/**
	     * Default controller method when controller is invoked
	     *
	     * @return void
	     */
		public function boot(Request $request) {}
        
        /**
	     * Get scripts for controller
	     *
	     * @return array
	     */
        protected function getScripts() {
	        
	        return $this->scripts;
	        
        }
        
        /**
	     * Enqueue scripts for controller
	     *
	     * @return void
	     */
        public function enqueueScripts() {
	        
			foreach($this->getScripts() as $script) {
				
				$script = is_array($script) ? $script : ['file' => $script];
				
				if ( $script['file'] = $this->getScriptPath( ! empty( $script['file'] ) ? $script['file'] : '' ) ) {
						
    				$info = pathinfo( $script['file'] );
    				
    				$extension = ! empty( $info['extension'] ) ? $info['extension'] : ( ! empty( $script['type'] ) ? $script['type'] : false );
    				
    				$theme = wp_get_theme();
    				$version = $theme->get('Version') ? $theme->get('Version') : '1.0.0';
    				
    				switch( $extension ) {
						
						case 'css' :
						
							$script = array_merge(array(
								'dependencies' => array(),
								'version' => $version,
								'media' => 'all',
								'enqueue' => true
							), $script, array(
								'handle' => ! empty( $script['handle'] ) ? $script['handle'] : $info['filename']
							));
							
							if( wp_style_is( $script['handle'], 'registered' ) ) {
    							
    							wp_deregister_style( $script['handle'] );
    							
							}
							
							wp_register_style(
								$script['handle'], 
								$script['file'], 
								$script['dependencies'], 
								$script['version'], 
								$script['media']
							);
							
							if( $script['enqueue'] ) {
								
								wp_enqueue_style($script['handle']);
								
							}
						
						break;
						
						default :
						
							$script = array_merge(array(
								'dependencies' => array(),
								'version' => $version,
								'in_footer' => true,
								'localize' => false,
								'enqueue' => true
							), $script, array(
								'handle' => ! empty( $script['handle'] ) ? $script['handle'] : $info['filename']
							));
							
							if( wp_script_is( $script['handle'], 'registered' ) ) {
    							
    							wp_deregister_script( $script['handle'] );
    							
							}
							
							wp_register_script(
								$script['handle'], 
								$script['file'], 
								$script['dependencies'], 
								$script['version'], 
								$script['in_footer']
							);
							
							if( $script['localize'] ) {
								
								wp_localize_script($script['handle'], $script['localize']['name'], $script['localize']['data']);
								
							}
							
							if( $script['enqueue'] ) {
							
								wp_enqueue_script($script['handle']);
								
							}
						
						break;
						
					}
    				
                }
				
			}
			
		}
		
		/**
	     * Get script file url
	     *
	     * @return string
	     */
		protected function getScriptPath( $file ) {
    		
    		if( ! filter_var( $file , FILTER_VALIDATE_URL) === false ) {
        		
        		return $file;
        		
            } 
            
            else if( $file = get_asset( $file ) ) {
             
                return $file;
                
            }
            
            return false;
    		
		}
	    
	    /**
	     * Before filter [legacy]
	     *
	     * @return void
	     */
        public function beforeFilter(Request $request) {
		
			$this->register($request);
	        
        }
	    
	    /**
	     * Dispatch [legacy]
	     *
	     * @return void
	     */
        public function dispatch(Request $request) {
		
			$this->boot($request);
	        
        }
        
    }
