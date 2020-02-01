<?php
    
    namespace WPKit\Invoker;
    
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Support\Facades\Request;
    
    class Invoker {
	    
	    /**
	     * @var Illuminate\Contracts\Container\Container
	     */
	    protected $app = null;
	    
	    /**
	     * @var static array
	     */
	    private static $invoked = [];
	    
	    /**
	     * The constructor
	     *
	     * @param  \Illuminate\Contracts\Container\Container  $app
	     * @return void
	     */
	    public function __construct(Container $app) {
		    
		    $this->app = $app;
		    
	    }
	    
	    /**
	     * Invoke match function
	     *
	     * @return void
	     */
	    public function match( $callback, $action = 'wp', $condition = null, $priority = null ) {
		    
		    $priority = is_null( $priority ) ? ( is_numeric( $condition ) ? $condition : 10 ) : $priority;
		    
		    if( is_null( $condition ) || $condition === $priority ) {
			    
			    $this->invoke( $callback, $action, $priority );
			    
		    } else {
			    
			    $this->invokeByCondition( $callback, $action, $condition, $priority );
			    
		    }
		    
	    }
	    
	    /**
	     * Invoke by condition
	     *
	     * @return void
	     */
	    protected function invokeByCondition( $callback, $action = 'wp', $condition = true, $priority = 10 ) {
			
			add_action( $action, function() use( $action, $callback, $condition, $priority ) {
			
				if( ( is_callable( $condition ) && call_user_func( $condition ) ) || ( ! is_callable( $condition ) && $condition ) ) {
					
					$this->invoke( $callback, $action, $priority );
				
				}
				
			}, $priority-1 );

		}
		
		/**
	     * Invoke function
	     *
	     * @return void
	     */
		protected function invoke( $callback, $action = 'wp', $priority = 10 ) {
			
			$callback = $this->getCallback( $callback );
			
			if( ! is_string( $callback ) || ! $this->invoked( $callback ) ) {
			
				add_action( $action, function() use( $action, $callback ) {
					
					$callback = $this->parseCallback($callback);
					
					$this->app->call( $callback, [ 'request' => $this->app->make( Request::class ) ] );
					
				}, $priority );
				
				if( is_string( $callback ) ) {
				
					$this->markAsInvoked( $callback );
					
				}
			
			}
			
		}
		
		/**
	     * Prepend namespace to route string
	     *
	     * @param  string  $callback
	     * @return string
	     */
		public function prependNamespace( $callback ) {
			
			if( is_string( $callback ) && strpos($callback, '\\') !== 0 ) {
				
				$config = $this->app['config.factory']->get('invoker');
				
				$parts = explode( '@', $callback );
				$parts[0] =  $config['namespace'] . '\\' . $parts[0];
				$callback = implode( '@', $parts );

			} 
			
			return $callback;
			
		}
		
		/**
	     * Parse callback to route string
	     *
	     * @param  string  $callback
	     * @return string
	     */
		public function parseCallback( $callback ) {
			
			if( is_string( $callback ) ) {
						
				$callback = $this->prependNamespace( $callback );
				
				$filter = implode( '@', [ explode( '@', $callback )[0], 'beforeFilter' ] );
				
				if( ! $this->invoked( $filter ) ) {
			
					$this->app->call( $filter, [ 'request' => $this->app->make( Request::class ) ] );
					
					$this->markAsInvoked( $filter );
					
				}
				
			}
			
			return $callback;
			
		}
		
		/**
	     * Mark as invoked
	     *
	     * @return void
	     */
		protected function markAsInvoked( $callback, $action = 1 ) {
			
			self::$invoked[$callback] = $action;
			
		}
		
		/**
	     * Check if is invoked
	     *
	     * @return boolean
	     */
		protected function invoked( $callback ) {
			
			return ! empty( self::$invoked[$callback] ) ? self::$invoked[$callback] : false;
			
		}
	    
	    /**
	     * Get callback
	     *
	     * @return string/closure
	     */
	    protected function getCallback( $callback ) {
		    
	        if ( is_string( $callback ) ) {
		        
		        if( strpos($callback, '@') === false ) {
				    
	            	$callback .= '@dispatch';
	            	
	            }
	            
	        }
	
	        return $callback;
	        
	    }
	    
	}
