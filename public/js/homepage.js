changeWeather()
setInterval(changeWeather, 60000);

async function changeWeather(){
    
    let weather_value = await (await fetch('/api/weather', {
		method: 'GET',
    })).json();
    const currentdate = new Date(); 
    const capitalize = (s) => {
      if (typeof s !== 'string') return ''
      return s.charAt(0).toUpperCase() + s.slice(1)
    }

    document.getElementById("weather_icon").setAttribute("src",'http://openweathermap.org/img/wn/' + weather_value['weather'][0]['icon'] + "@4x.png");
    document.getElementById("weather_status").innerHTML = capitalize(weather_value['weather'][0]['description'])
    document.getElementById("temperature_celsius").innerHTML = Math.floor(weather_value['main']['temp']) + "º"
    document.getElementById("city").innerHTML = weather_value['name']
    document.getElementById("date").innerHTML = currentdate.toLocaleDateString(['en-GB'],{ weekday: 'short', month: 'numeric', day: 'numeric' });
    document.getElementById("time").innerHTML = currentdate.toLocaleTimeString([],{ hour12: false ,hour: '2-digit', minute: '2-digit' });

}