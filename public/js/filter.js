$(function () {
    $('#fromFilter').datetimepicker({
        format: 'L',
        buttons: {
            showClose: true,
            showClear: true
        },
        icons: {
            clear: 'fa fa-trash',
        },
        useCurrent: false,
    });
    $('#toFilter').datetimepicker({
        format: 'L',
        buttons: {
            showClose: true,
            showClear: true
        },
        icons: {
            clear: 'fa fa-trash',
        },
        useCurrent: false,
    });

    $("#fromFilter").on("change.datetimepicker", function (e) {
        $('#toFilter').datetimepicker('minDate', e.date);
    });
    $("#toFilter").on("change.datetimepicker", function (e) {
        $('#fromFilter').datetimepicker('maxDate', e.date);
    });
});


let slider = document.getElementById('likes-slider')

noUiSlider.create(slider, {
    start: [0, 1000],
    connect: true,
    step: 1,
    range: {
        'min': -100,
        'max': 1000
    }
})

let minLikes = document.getElementById('minLikes')
let maxLikes = document.getElementById('maxLikes')


slider.noUiSlider.on('update', function (values, handle) {

    var value = values[handle];

    if (handle) {
        maxLikes.value = Math.round(value);
    } else {
        minLikes.value = Math.round(value);
    }
});

minLikes.addEventListener('change', function () {
    slider.noUiSlider.set([this.value, null]);
});

maxLikes.addEventListener('change', function () {
    slider.noUiSlider.set([null, this.value]);
});

let closeBtn = document.getElementById('close-sidebar')
let pageWrapper = document.getElementById('wrapper')
closeBtn.addEventListener('click', function(ev){
    ev.preventDefault()
    pageWrapper.classList.remove('toggled')

})

let openBtn = document.getElementById('show-sidebar')
openBtn.addEventListener('click', function(ev){
    ev.preventDefault()
    pageWrapper.classList.add('toggled')
})


function disableCloseBt(x) {
    if (x.matches){
        closeBtn.disabled = true
        pageWrapper.classList.add('toggled')
    }
    else
        closeBtn.disabled = false
}
  
let x = window.matchMedia("(min-width: 992px)")
disableCloseBt(x) // Call listener function at run time
x.addListener(disableCloseBt) 

function vh(v) {
    var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    return (v * h) / 100;
  }
function convertRemToPixels(rem) {    
    return rem * parseFloat(getComputedStyle(document.documentElement).fontSize);
}

let sideFilter = document.getElementById('sidebar')
sideFilter.style.maxHeight = vh(100) - convertRemToPixels(3) + 'px'


function yOff() {
    return document.getElementsByTagName('body')[0].offsetHeight - window.scrollY - window.innerHeight - document.getElementById('main-footer').offsetHeight    
}

