<?php

namespace Cooglemirror\Weather;

use Cmfcmf\OpenWeatherMap;
use Carbon\Carbon;
class Widget
{
    public function compose($view)
    {
        $map = new OpenWeatherMap(\Config::get('cooglemirror-weather::widget.openweathermap.api_key'));
        
        $city = \Config::get('cooglemirror-weather::widget.city_id', \Config::get('cooglemirror-weather::widget.city', null));
        
        if(is_null($city)) {
            return;
        }

        $weatherForecast = null;
        $currentWeather = null;
        
        try {
            $weatherForecast = $map->getWeatherForecast(
                $city,
                \Config::get('cooglemirror-weather::widget.openweathermap.units'),
                \Config::get('cooglemirror-weather::widget.openweathermap.language'),
                0
            );
            
            $currentWeather = $map->getWeather(
                $city,
                \Config::get('cooglemirror-weather::widget.openweathermap.units'),
                \Config::get('cooglemirror-weather::widget.openweathermap.language')
            );
            
            $expiresAt = Carbon::now()->addHour();
            
            \Cache::put('cooglemirror-weather::weatherForecast', $weatherForecast, $expiresAt);
            \Cache::put('cooglemirror-weather::currentWeather', $currentWeather, $expiresAt);
            \Cache::put('cooglemirror-weather::updateTime', Carbon::now(\Config::get('app.timezone'))->format('g:ia'), $expiresAt);
            
        } catch(\Exception $e) {
            if(\Cache::has('cooglemirror-weather::updateTime')) {
                $view->with('messsage', 'Updated last: ' . \Cache::get('cooglemirror-weather::updateTime'));
            }
            
            if(\Cache::has('cooglemirror-weather::weatherForecast')) {
                $weatherForecast = \Cache::get('cooglemirror-weather::weatherForecast');
            }
            
            if(\Cache::has('cooglemirror-weather::currentWeather')) {
                $currentWeather = \Cache::get('cooglemirror-weather::currentWeather');
            }
        }
        
        if(is_null($currentWeather)) {
            $weatherData = [
                'sun' => [
                    'rise' => '??:??',
                    'set' => '??:??'
                ],
                'current' => [
                    'temp' => '??&deg;',
                    'icon' => 'wi-na'
                ]
            ]; 
        } else {
            $sunRise = Carbon::createFromTimestamp($currentWeather->sun->rise->getTimestamp(), \Config::get('app.timezone'));
            $sunSet = Carbon::createFromTimestamp($currentWeather->sun->set->getTimestamp(), \Config::get('app.timezone'));
            
            $weatherData = [
                'sun' => [
                    'rise' => $sunRise->format('g:ia'),
                    'set' => $sunSet->format('g:ia')
                ],
                'current' => [
                    'temp' => round($currentWeather->temperature->now->getValue(), 0) . "&deg;",
                    'icon' => (date('m-d') == '04-25') ? 'wi-alien' : $this->convertIcon($currentWeather->weather->icon)
                ]
            ];
        }
        
        $weatherData['hourly'] = [];
        
        if(!is_null($weatherForecast)) {
            $hourCount = \Config::get('cooglemirror-weather::widget.hours', 4);
            
            $i = 0;
            $opacity = 1;
            foreach($weatherForecast as $forecast) {
                $weatherData['hourly'][] = [
                    'hour' => $forecast->time->from->format('g A'),
                    'temp' => round($forecast->temperature->getValue(), 0) . "&deg;",
                    'icon' => $this->convertIcon($forecast->weather->icon),
                    'opacity' => $opacity
                ];
                
                $opacity -= (1 / ($hourCount + 1));
                
                if(++$i >= $hourCount) {
                    break;
                }
            }
        }
        
        $view->with('weatherData', $weatherData);
            
    }
    
    protected function convertIcon($icon)
    {
        switch($icon) {
            case '01d':
                return "wi-day-sunny";
            case '02d':
                return 'wi-day-cloudy';
            case '03d':
                return 'wi-cloudy';
            case '04d':
                return 'wi-cloudy-windy';
            case '09d':
                return 'wi-showers';
            case '10d':
                return 'wi-rain';
            case '11d':
                return 'wi-thunderstorm';
            case '13d':
                return 'wi-snow';
            case '50d':
                return 'wi-fog';
            case '01n':
                return 'wi-stars';
            case '02n':
                return 'wi-night-cloudy';
            case '03n':
                return 'wi-night-cloudy';
            case '04n':
                return 'wi-night-alt-cloudy-windy';
            case '09n':
                return 'wi-night-showers';
            case '10n':
                return 'wi-night-rain';
            case '11n':
                return 'wi-night-thunderstorm';
            case '13n':
                return 'wi-night-snow';
            case '50n':
                return 'wi-night-alt-cloudy-windy';
        }
        
        return 'wi-na';
    }
}