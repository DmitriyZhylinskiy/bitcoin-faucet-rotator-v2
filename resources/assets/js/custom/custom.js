$.ajaxSetup({
    timeout: 3600000,

    // force ajax call on all browsers
    cache: false,

    // Enables cross domain requests
    crossDomain: true,

    // Helps in setting cookie
    xhrFields: {
        withCredentials: true
    },

    beforeSend: function (xhr, type) {
        // Set the CSRF Token in the header for security
        //if (type.type !== "GET") {
        var token = Cookies.get("XSRF-TOKEN");
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-XSRF-Token', token);
        //}
    }
});

$(window).ready(function () {
    footerReset();
});

$(window).resize(function () {
    footerReset();
});

window.addEventListener("load", function () {
    window.cookieconsent.initialise({
        "palette": {
            "popup": {
                "background": "#252e39"
            },
            "button": {
                "background": "#14a7d0"
            }
        },
        "theme": "classic"
    })
});

Number.prototype.nth= function () {
    if (this%1) {
        return this;
    }
    var s= this%100;
    if (s>3 && s<21) {
        return this+'th';
    }
    switch (s%10) {
        case 1: return this+'st';
        case 2: return this+'nd';
        case 3: return this+'rd';
        default: return this+'th';
    }
};

Date.prototype.formatDDMMYYYY = function () {
    return ('0' + (this.getDate())).slice(-2) +
        "/" +  ('0' + (this.getMonth() + 1)).slice(-2) +
        "/" +  this.getFullYear();
};

function keyExists(key, search) {
    if (!search || (search.constructor !== Array && search.constructor !== Object)) {
        return false;
    }
    for (var i = 0; i < search.length; i++) {
        if (search[i] === key) {
            return true;
        }
    }
    return key in search;
}

function footerReset()
{
    var contentHeight = $(window).height();
    var footer = $('#footer-custom');
    if (typeof footer.position() !== 'undefined') {
        var footerHeight = footer.height();
        var footerTop = footer.position().top + footerHeight;
        if (footerTop < contentHeight) {
            footer.css('margin-top', (contentHeight*0.99 - (footerTop) - 32) + 'px');
        } else {
            footer.css('margin-top', ((contentHeight/10) - 32) + 'px');
        }
    }
}

function getRandomRgb()
{
    var num = Math.round(0xffffff * Math.random());
    return {
        r: num >> 16,
        g: num >> 8 & 255,
        b: num & 255
    }
}

function rgbaString(r, g, b, a)
{
    var opacity = 1;
    if (typeof a !== 'undefined') {
        opacity = a;
    }
    if (typeof r !== 'undefined' && typeof g !== 'undefined' && typeof b !== 'undefined') {
        return "rgba(" + r + "," + g + "," + b + "," + opacity + ")";
    } else {
        return null;
    }
}

function getRandomHexColor()
{
    var length = 6;
    var chars = '0123456789ABCDEF';
    var hex = '#';
    while (length--) {
        hex += chars[(Math.random() * 16) | 0];
    }
    return hex;
}

function progressError(dataObject, progressBar)
{
    if (dataObject !== null && typeof dataObject !== 'undefined' && typeof progressBar !== 'undefined') {
        var errorMessage;

        if (typeof dataObject === 'string') {
            errorMessage = dataObject;
        } else if (dataObject.message !== 'undefined') {
            errorMessage = dataObject.message;
        } else {
            errorMessage = "Unknown error.";
        }

        progressBar.progressTimer('error', {
            errorText: '<strong>ERROR! - ' + errorMessage + '</strong>',
            warningStyle: 'progress-bar-danger'
        });
    }
}

function generateProgressBar(elementName, dataName)
{
    if (typeof $(elementName) !== 'undefined' && dataName !== 'undefined') {
        return $(elementName).progressTimer({
            timeLimit: 600,
            baseStyle: 'progress-bar-info',
            warningThreshold: 180,
            warningStyle: 'progress-bar-warning',
            completeStyle: 'progress-bar-success',
            successText: '<strong>100% - ' + dataName + ' data loading complete!</strong>'
        });
    }
}

function hideElement(elementName, timeoutValue)
{
    if (typeof $(elementName) !== 'undefined') {
        typeof timeoutValue === 'undefined' ? timeoutValue = 0 : timeoutValue;
        return setTimeout(function () {
            $(elementName).hide();
        }, timeoutValue);
    }
}

function showElement(elementName, timeoutValue)
{
    if (typeof $(elementName) !== 'undefined') {
        typeof timeoutValue === 'undefined' ? timeoutValue = 0 : timeoutValue;
        return setTimeout(function () {
            $(elementName).show();
        }, timeoutValue);
    }
}

function currentDate(separator)
{

    if (typeof separator === 'undefined') {
        separator = '-';
    }
    var d = new Date();
    var day = ('0' + (d.getDate())).slice(-2);
    var month = ('0' + (d.getMonth()+1)).slice(-2);
    var year = d.getFullYear();

    return {
        date: d,
        dateFormatted:day + separator + month + separator + year,
        fullDisplay: formatDateFull(d)
    }
}

function dateFormatted(date, separator)
{
    if (typeof date !== 'undefined' && date.constructor === Date) {
        if (typeof separator === 'undefined') {
            separator = '-';
        }
        var day = ("0" + (date.getDate())).slice(-2);
        var month = ("0" + (date.getMonth()+1)).slice(-2);
        var year = date.getFullYear();

        return {
            date: date,
            dateFormatted:day + separator + month + separator + year,
            fullDisplay: formatDateFull(date)
        }
    } else {
        return null;
    }
}

function formatDateFull(date)
{
    if (typeof date !== 'undefined' && date !== null) {
        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        var displayDay = days[date.getDay()];
        var displayDate = date.getDate().nth();
        var displayMonth = ['January','February','March','April','May','June','July','August','September','October','November','December']
            [date.getMonth()];
        var displayYear = date.getFullYear();

        return displayDay + ' ' + displayDate + ' ' + displayMonth + ' ' + displayYear;
    }
}

function alterDate(date, amount)
{
    var timezoneOffset = date.getTimezoneOffset() * 60 * 1000,
        t = date.getTime(),
        d = new Date();

    if (typeof amount !== 'undefined' && Number.isInteger(amount)) {
        t += (1000 * 60 * 60 * 24 * amount);

        d.setTime(t);

        var timezoneOffset2 = d.getTimezoneOffset() * 60 * 1000;
        if (timezoneOffset !== timezoneOffset2) {
            t += timezoneOffset2 - timezoneOffset;
            d.setTime(t);
        }

        return d;
    } else {
        return null;
    }
}

function currentLanguage()
{
    var language = window.navigator.userLanguage || window.navigator.language;

    return language !== 'undefined' ? language : navigator.language;
}

function renderVisitorsAreaChart(data, chartElement, chartContainerElement, chartHeight, progressBar, doUpdate)
{

    if (chartHeight === 'undefined' || chartHeight === null) {
        chartHeight = "20em";
    }

    if (data.status !== 'undefined' && data.status === 'error') {
        showElement(progressBar);
        progressError(data.message, progressBar);
        return false;
    } else {
        data.done(function (vacd) {
            if (typeof vacd.status !== 'undefined' && vacd.status === 'error') {
                showElement(progressBar);
                progressError(vacd.message, progressBar);
                return false;
            } else {
                if (doUpdate === true) {
                    clearChart(chartElement, chartContainerElement, chartHeight);
                }

                showElement(progressBar);
                generateVisitorsLineChart(vacd, chartElement);
                progressBar.progressTimer('complete');
                hideElement(progressBar, 3000);
                return true;
            }
        }).fail(function (vacd) {

            showElement(progressBar);
            progressError(vacd.message,progressBar);
            return false;
        }).progress(function () {
            console.log("Visitors area chart is loading...");
        });
    }
}

function renderVisitorsDataTable(data, dataTableElement, progressBar, doUpdate)
{

    $(dataTableElement).DataTable().destroy();
    $(dataTableElement + ' tbody').empty();

    showElement(progressBar);

    var loadingProgressElement = $(dataTableElement + '_processing');
    loadingProgressElement.attr('style', 'display:initial !important');
    if (data.status !== 'undefined' && data.status === 'error') {
        progressError(
            data.message,
            progressBar
        );
    } else {
        data.done(function (vd) {
            if (typeof vd.status !== 'undefined' && vd.status === 'error') {
                progressError(
                    vd.message,
                    progressBar
                );
            } else {
                if (doUpdate === true) {
                    var dataTable = generateVisitorsTable(vd.data, dataTableElement);

                    loadingProgressElement.attr('style', 'display:none !important');
                    progressBar.progressTimer('complete');
                    hideElement(progressBar, 3000);

                    return dataTable;
                } else {
                    loadingProgressElement.attr('style', 'display:none !important');
                    progressBar.progressTimer('complete');
                    hideElement(progressBar, 3000);

                    return generateVisitorsTable(vd.data, dataTableElement);
                }
            }
        }).fail(function (vd) {
            loadingProgressElement.attr('style', 'display:none !important');
            showElement(progressBar);
            progressError(vd.message,progressBar);
        }).progress(function () {
            console.log("Visitors datatable is loading...");
        });
    }
}

function renderVisitorsGoogleMap(data, mapElement, progressBar, mapWidth,mapHeight, doUpdate)
{
    if (typeof $(mapElement) !== 'undefined' && typeof progressBar !== 'undefined') {
        if (data.status !== 'undefined' && data.status === 'error') {
            showElement(progressBar);
            progressError(
                data.message,
                progressBar
            );
        } else {
            data.done(function (d) {

                if (typeof d.status !== 'undefined' && d.status === 'error') {
                    showElement(progressBar);
                    progressError(
                        d.message,
                        progressBar
                    );
                } else {
                    if (doUpdate === true) {
                        $(mapElement).empty();
                    }

                    showElement(progressBar);
                    $(mapElement).attr('style', 'height:' + mapHeight + ';width: ' + mapWidth + ' !important');
                    generateGoogleGeoChart(d, mapElement);
                    progressBar.progressTimer('complete');
                    hideElement(progressBar, 3000);
                }
            }).fail(function (d) {

                showElement(progressBar);
                progressError(d.message,progressBar);
            }).progress(function () {
                console.log("Visitors geo chart is loading...");
            });
        }
    }
}

function renderBrowserStatsPieChart(data, chartElement, chartContainerElement, chartHeight, progressBar, doUpdate)
{

    if (chartHeight === 'undefined' || chartHeight === null) {
        chartHeight = "35em";
    }

    if (data.status !== 'undefined' && data.status === 'error') {
        showElement(progressBar);
        progressError(
            data.message,
            progressBar
        );
    } else {
        data.done(function (d) {

            if (typeof d.status !== 'undefined' && d.status === 'error') {
                showElement(progressBar);
                progressError(
                    d.message,
                    progressBar
                );
            } else {
                if (doUpdate === true) {
                    clearChart(chartElement, chartContainerElement, chartHeight);
                }
                showElement(progressBar);
                generatePieDonutChart(d,chartElement);
                progressBar.progressTimer('complete');
                hideElement(progressBar, 3000);
            }
        }).fail(function (d) {

            showElement(progressBar);
            progressError(
                d.message,
                progressBar
            );
        }).progress(function () {
            console.log("Visitors' browsers chart is loading...");
        });
    }
}

function clearChart(chartElement, chartContainerElement, chartHeight)
{

    if (chartHeight === 'undefined' || chartHeight === null) {
        chartHeight = "35em";
    }

    if (typeof $(chartContainerElement) !== 'undefined' && typeof $(chartElement) !== 'undefined') {
        $(chartContainerElement).empty();

        var attribute = 'id';

        if (chartElement.substring(0,1) === '.') {
            attribute = 'class';
        } else if (chartElement.substring(0,1) === '#') {
            attribute = 'id';
        }

        var elementName = chartElement.substring(1);

        $(chartContainerElement).append('<canvas ' + attribute + '="' + elementName + '" style="height:' + chartHeight + '"></canvas>');
    }
}

function generateSwitch(elem, initState, onText, offText)
{
    if(jQuery().bootstrapSwitch){
        if(elem !== 'undefined' && (elem.attr('type') === 'checkbox' || elem.attr('type') === 'radio')){

            elem.on('switchChange.bootstrapSwitch', function(event,  state) {
                if(+state === 1){
                    elem.attr('checked', 'checked');
                    elem.val(parseInt(+state));
                } else{
                    elem.attr('checked', '');
                    elem.val(parseInt(+state));
                }
            });

            return elem.bootstrapSwitch({
                onText: onText !== null && typeof onText !== 'undefined' ? onText : 'Yes',
                offText: offText !== null && typeof offText !== 'undefined' ? offText : 'No',
                state: initState
            });
        }
    }
}

function toggleState(checkedBox, othercheckBoxes)
{
    if(jQuery().bootstrapSwitch){
        if(checkedBox !== 'undefined' && (checkedBox.attr('type') === 'checkbox' || checkedBox.attr('type') === 'radio')){
            checkedBox.on('switchChange.bootstrapSwitch', function(){
                if(this.checked && Array.isArray(othercheckBoxes)){
                    for(var i = 0; i < othercheckBoxes.length; i++){
                        if(othercheckBoxes[i].attr('type') === 'checkbox' || othercheckBoxes[i].attr('type') === 'radio'){
                            othercheckBoxes[i].val(+!this.checked);
                            othercheckBoxes[i].bootstrapSwitch('state', !this.checked, true);
                        }
                    }
                } else if(othercheckBoxes !== null && !Array.isArray(othercheckBoxes) &&
                    (othercheckBoxes.attr('type') === 'checkbox' || othercheckBoxes.attr('type') === 'radio')
                ){
                    othercheckBoxes.val(+!this.checked);
                    othercheckBoxes.bootstrapSwitch('state', !this.checked, true);
                }
            });
        }
    }
}

function generateDateTimePicker(elem){
    if(elem !== 'undefined' && elem.attr('type') === 'text' && jQuery().datetimepicker){
        elem.datetimepicker({
            dateFormat: "dd/mm/yy",
            timeFormat: 'HH:mm:ss',
            stepHour: 1,
            stepMinute: 1,
            stepSecond: 1,
            sliderAccessArgs: { touchonly: false }
        });
    }
}