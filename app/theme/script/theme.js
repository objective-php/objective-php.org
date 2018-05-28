import 'bootstrap';

$(function () {
    let currentCompo = '';
    if (window.location.pathname.split('/')[2] in dataMenu) {
        currentCompo = window.location.pathname.split('/')[2]
    }

    //On choisit les versions des composants
    let docMenuInfos = {}
    if (readCookie('docMenuInfos')) {
        docMenuInfos = JSON.parse(readCookie('docMenuInfos'))
    } else {
        $.each(dataMenu, (compoName, minorVersions) =>
            docMenuInfos[compoName] = Object.keys(minorVersions).reduce((a, b) => a > b ? a : b)
        );
        createCookie('docMenuInfos', JSON.stringify(docMenuInfos))
    }

    if (currentCompo) {
        //On construit le dropdown des versions
        $('#version-dropdown').html(' <button id="version-select" type="button" class="btn btn-tertiary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
            '            </button>\n' +
            '            <div id="version-options" class="dropdown-menu">\n' +
            '            </div>')
        $('#version-select').html(docMenuInfos[currentCompo])
        $.each(dataMenu[currentCompo], (minorVersion, val) =>
            $('#version-options')
                .append('<a class="dropdown-item" href="/doc/' + currentCompo + '/' + minorVersion + '/' + window.location.pathname.split('/').pop() + '">' + minorVersion + '</a>')
                .children().last().click(() => {
                docMenuInfos[currentCompo] = minorVersion
                createCookie('docMenuInfos', JSON.stringify(docMenuInfos))
            })
        )
    }
    //On construit l'html du menu
    let docMenu = '<ul>'
    $.each(docMenuInfos, (compoName, minorVersion) => {
        docMenu += '<li><div class="hd"><i class="fas fa-angle-right fa-lg"></i>'
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
            $(this).parent().parent().toggleClass('opened');
            createCookie($(this).next().text(), $(this).parent().parent().attr('class'));
        })
        .each(function (index, element) {
            if (readCookie($(element).next().text()) == 'opened')
                $(element).parent().parent().attr('class', 'opened')
            else
                $(element).parent().parent().attr('class', '')
        });

});


function createCookie (name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie (name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

