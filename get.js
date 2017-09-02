/*
 * This file is part of the Vistar project.
 * This source code under MIT license
 *
 * Copyright (c)  2017 Vistar project <https://github.com/vistarsvo/>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

var page = require('webpage').create();
page.viewportSize = { width: 1800, height: 950 };

var system = require('system');
// Options
var followRedirects = false;
var userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0';
// Answert parts
var headers = {};
var redirects = {};
var answer = {};
var other = {};

/**
 * Check an arguments count. If is null - return error
 */
if (system.args.length === 1) {
    answer['result'] = 'error';
    answer['message'] = 'Usage: loadspeed.js <some URL>';
    answer['status'] = -1;
    printAnswer();
}

/** @var Go Url **/
var goUrl = system.args[1];
/** @var Timeout - for time-out before get and return content **/
var contentTimeOut = 0;
/**
 * Flag only for first request load
 * @type {boolean}
 */
var meFirst = true;


/**
 * Check follow redirects option
 */
if (system.args.length > 2) {
    followRedirects = system.args[2];
    if (followRedirects == '1' || followRedirects == 'true') {
        followRedirects = true;
        other['followRedirects'] = 'yes';
    } else {
        other['followRedirects'] = 'no';
    }
} else {
    other['followRedirects'] = 'default';
}

/**
 * Set-up userAgent
 */
if (system.args.length > 3) {
    userAgent = system.args[3];
    if (userAgent !== 'default') {
        page.settings.userAgent = userAgent;
    }
}

/**
 * Set-up timeout
 */
if (system.args.length > 4) {
    contentTimeOut = parseInt(system.args[4]);
    contentTimeOut = contentTimeOut * 1000;
}

/**
 * Set start time
 * @type {number}
 */
other['timeStart'] = new Date().getTime();
other['userAgent'] = page.settings.userAgent;

/**
 * Print answer object and close phantomJS
 */
function printAnswer() {
    var ans = {'answer' : answer, 'other' : other, 'headers' : headers, 'redirects' : redirects};
    console.log(JSON.stringify(ans));
    phantom.exit();
}

/**
 * On URL change listener for redirects collected
 * @param targetUrl
 */
page.onUrlChanged = function(targetUrl) {
    if (meFirst) {
        redirects[redirects.length + 1] = targetUrl;
    }
};

/**
 * Finish loading time
 * @param status
 */
page.onLoadFinished = function(status) {
    if (meFirst) {
        other['timeEnd'] = new Date().getTime();
        other['status'] = status;
    }
};



/**
 * For get status code and headers.
 * @param response
 */
page.onResourceReceived = function(response) {
    if (meFirst) {
        headers = response.headers;

        // Add to headers some info
        headers[headers.length] = {name:'Http-Code', value:response.status};
        headers[headers.length] = {name:'Content-Length', value:response.bodySize};

        // Build answer object
        answer['statusCode'] = response.status;
        answer['statusText'] = response.statusText;
        answer['contentType'] = response.contentType;
        answer['answerTime'] = response.time;
        answer['bodySize'] = response.bodySize;
        answer['url'] = response.url;
        answer['redirectUrl'] = response.redirectURL;

        // If is not 200 OK || not 206 Partial
        // i think it is most important answers for check
        if ((response.status !== 200 && response.status !== 206) &&
            response.headers.filter(function(header) {
                if (header.name == 'Content-Type' && header.value.indexOf('text/html') == 0) {
                    return true;
                }
                return false;
            }).length > 0
        ) {
            // If it is no follow redirects AND has redirect - return on OK result
            if (!followRedirects &&
                    /* useful Redirects answers */
                    ( response.status == 301 || response.status == 302 || response.status == 307 || response.status == 308)
                ) {
                other['timeEnd'] = new Date().getTime();
                answer['result'] = 'error';
                answer['message'] = 'Not OK';
                printAnswer();
            }
        }
        other['timeEnd'] = new Date().getTime();
    }
    meFirst = false;
};


/**
 * Open an URL
 * Return answer Object
 */
page.open(goUrl, function (status) {
    if (status !== 'success') {
        answer['result'] = 'error';
        answer['status'] = status;
        answer['message'] = 'Can not load page';
        printAnswer();
    } else {
        answer['result'] = 'success';
        answer['status'] = status;
        if (contentTimeOut > 0) {
            setTimeout(function() {
                other['timeOut'] = (contentTimeOut / 1000) + 'sec';
                var ctn = page.evaluate(function () {
                    return document.body.innerHTML;
                });
                answer['content'] = ctn;
                printAnswer();
            }, contentTimeOut);
        } else {
            answer['content'] = page.content;
            printAnswer();
        }
    }
});