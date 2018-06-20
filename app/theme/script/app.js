import 'bootstrap';
import 'lodash';
import hljs from "highlight.js";
$(function () {

    let docfiles = (compoName, minor) => {
        let menuEl = `<div class="hd"><i class="fa fa-angle-right fa-lg"></i>`
        menuEl += `<a href="/doc/${compoName}/${minor}/index.html">${compoName}</a>`
        menuEl += '</div>'
        menuEl += '<div class="bd"><ul>'
        $.each(dataMenu[compoName][minor], (nice, raw) => {
            menuEl += '<li><div class="hb leaf">'
            menuEl += `<a href="/doc/${compoName}/${minor}/${raw}">${nice}</a>`
            menuEl += '</div></li>'
        })
        menuEl += `<li><div class="hb leaf"><a href="/doc/${compoName}/${minor}/api/index.html">API</a></div></li>`;
        menuEl += '</ul></div>'
        return menuEl
    }

    let $apitree = $('#api-tree');
    if ($apitree.length) {

        if (!util.readCookie('md5Hash') || util.readCookie('md5Hash') != md5Hash) {
            util.eraseCookie('theMenu')
            util.createCookie('md5Hash', md5Hash)
        }
        $("a[href*=\\#]:not([href=\\#])").click(function () {
            if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '')
                || location.hostname == this.hostname) {
                window.util.scrollToAnchor(this.hash);
            }
        });
        if (window.location.hash) {
            window.util.scrollToAnchor(window.location.hash);
        }

        let currentCompo = '';
        if (window.location.pathname.split('/')[2] in dataMenu) {
            currentCompo = window.location.pathname.split('/')[2]
        }

        let theMenu = ''
        if (util.readCookie('theMenu')) {
            theMenu = JSON.parse(util.readCookie('theMenu'))
        } else {
            theMenu = '<ul>'
            $.each(dataMenu, (compoName, minorVersion) => {

                let versions = {}
                Object.keys(minorVersion).sort((a, b) => {
                    return b - a;
                }).forEach(function (key) {
                    versions[key] = minorVersion[key];
                });
                let minor = Object.keys(versions)[0]
                theMenu += `<li class="menu-${compoName}" >`
                theMenu += docfiles(compoName, minor)
                theMenu += '</li>'
            })
            theMenu += '<ul>'
            util.createCookie('theMenu', JSON.stringify(theMenu))
        }


        $('#api-tree').html(theMenu)

        let menuToCookie = () => {
            util.createCookie('theMenu', JSON.stringify($('#api-tree').html()))
        }

        $('#api-tree .hd i').click(function () {
            $(this).parent().parent().toggleClass('opened');
            menuToCookie()
        })

        if (currentCompo) {
            //On construit le dropdown des versions
            $('#version-dropdown').html(' <button id="version-select" type="button" class="btn btn-light btn-lg dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                '            </button>\n' +
                '            <div id="version-options" class="dropdown-menu">\n' +
                '            </div>')
            $('#version-select').html(window.location.pathname.split('/')[3])
            let versions = {}
            Object.keys(dataMenu[currentCompo]).sort((a, b) => {
                return b - a;
            }).forEach(function (key) {
                versions[key] = dataMenu[currentCompo][key];
            });
            $.each(versions, (minorVersion, val) => {
                    let random = Math.random().toString(36).substr(2, 16);
                    $('#version-options')
                        .append(`<a id="option-${currentCompo}-${random}" class="dropdown-item" href="/doc/${currentCompo}/${minorVersion}/${Object.values(val).includes(window.location.pathname.split('/')[4]) ? window.location.pathname.split('/')[4] : 'index.html'}">${minorVersion}</a>`)
                    $(`#option-${currentCompo}-${random}`).on('click', {currentCompo, minorVersion}, function () {
                        $(`li.menu-${currentCompo}`).html(docfiles(currentCompo, minorVersion))
                        menuToCookie()
                    });
                }
            )

            //Place en premier le menu-composant qui est consulte
            $('#api-tree > ul').children().removeClass('opened')
            $('#api-tree > ul').prepend($(`.menu-${currentCompo}`).addClass('opened'))
        }
    }
})
$(document).ready(function() {
    hljs.initHighlightingOnLoad()

    $('pre code').each(function(i, block) {
        hljs.highlightBlock(block);
    });
});

window.util = new function () {
    this.createCookie = (name, value, days) => {
        let expires = ''
        if (days) {
            let date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = `; expires=${date.toGMTString()}`
        }
        document.cookie = `${name}=${value}${expires}; path=/`
    }

    this.readCookie = (name) => {
        let nameEQ = `${name}=`;
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    this.eraseCookie = (name) => {
        util.createCookie(name, '', -1);
    }

    this.capitalizeFirstLetter = (string) => {
        return string[0].toUpperCase() + string.slice(1);
    }

    this.scrollToAnchor = (hash) => {
        let target = $(hash),
            headerHeight = $('.fixed-top').height() + $('#site-nav').height() + $('#api-nav').height() + 25;

        target = target.length ? target : $('[name=' + hash.slice(1) + ']');

        if (target.length) {
            $('html,body').animate({
                scrollTop: target.offset().top - headerHeight
            }, 500);
            return false;
        }
    }
}

