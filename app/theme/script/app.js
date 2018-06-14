import 'bootstrap';
import 'lodash';

$(function () {
    let $apitree = $('#api-tree');
    if ($apitree.length) {
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

        //On choisit les versions des composants
        let docMenuInfos = {}
        if (util.readCookie('docMenuInfos')) {
            docMenuInfos = JSON.parse(util.readCookie('docMenuInfos'))
        } else {
            $.each(dataMenu, (compoName, minorVersions) =>
                docMenuInfos[compoName] = Object.keys(minorVersions).reduce((a, b) => a > b ? a : b)
            );
            util.createCookie('docMenuInfos', JSON.stringify(docMenuInfos))
        }

        //On construit l'html du menu
        let docMenu = '<ul>'
        $.each(docMenuInfos, (compoName, minorVersion) => {
            docMenu += `<li class="menu-${compoName}" ><div class="hd"><i class="fa fa-angle-right fa-lg"></i>`
            docMenu += `<a href="/doc/${compoName}/${minorVersion}/index.html">${compoName}</a>`
            docMenu += '</div><div class="bd"><ul>'
            $.each(dataMenu[compoName][minorVersion], (nice, raw) => {
                docMenu += '<li><div class="hb leaf">'
                docMenu += `<a href="/doc/${compoName}/${minorVersion}/${raw}">${nice}</a>`
                docMenu += '</div></li>'
            })
            docMenu += `<li><div class="hb leaf"><a href="/doc/${compoName}/${minorVersion}/api/index.html">API</a></div></li>`;
            docMenu += '</ul></div></li>'
        })
        docMenu += '<ul>'

        $('#api-tree').html(docMenu)

        $('#api-tree .hd i')
            .click(function () {
                let $li = $(this).parent().parent().toggleClass('opened');
                util.createCookie($(this).next().text(), $li.attr('class'));
            })
            .each(function (index, element) {
                let $li = $(element).parent().parent().removeClass('opened')
                if (util.readCookie($(element).next().text()) && util.readCookie($(element).next().text()).includes('opened'))
                    $li.addClass('opened')
            });

        if (currentCompo) {
            //On construit le dropdown des versions
            $('#version-dropdown').html(' <button id="version-select" type="button" class="btn btn-light btn-lg dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                '            </button>\n' +
                '            <div id="version-options" class="dropdown-menu">\n' +
                '            </div>')
            $('#version-select').html(docMenuInfos[currentCompo])
            $.each(dataMenu[currentCompo], (minorVersion, val) =>
                $('#version-options')
                    .append(`<a class="dropdown-item" href="/doc/${currentCompo}/${minorVersion}/${Object.values(val).includes(window.location.pathname.split('/')[4]) ? window.location.pathname.split('/')[4] : 'index.html'}">${minorVersion}</a>`)
                    .children().last().click(() => {
                    docMenuInfos[currentCompo] = minorVersion
                    util.createCookie('docMenuInfos', JSON.stringify(docMenuInfos))
                })
            )
            //Place en premier le menu-composant qui est consulte
            $('#api-tree > ul').prepend($(`.menu-${currentCompo}`).removeClass('opened').addClass('opened'))
        }
    }
})

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
