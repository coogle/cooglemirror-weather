<?php namespace Cooglemirror\Weather;

use Illuminate\Support\ServiceProvider;

class WeatherServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('cooglemirror/weather', 'cooglemirror-weather');
		
		\Event::listen('cooglemirror.render', function($layoutView) {
		    \View::composer('cooglemirror-weather::widget', 'Cooglemirror\Weather\Widget');
		    $view = \View::make('cooglemirror-weather::widget')->render();
		    $layoutView->with(\Config::get('cooglemirror-weather::widget.placement'), $view);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
