//глобальные функции для работы с куками
//как пользоваться:
//window.siteSetCookie('name',val, days);
//window.siteGetCookie('name');

function siteSetCookie(name, value, daysToLive) {
    // Encode value in order to escape semicolons, commas, and whitespace
    var cookie = name + "=" + encodeURIComponent(value);
    if(typeof daysToLive === "number") {
        // Sets the max-age attribute so that the cookie expires after the specified number of days
        cookie += "; max-age=" + (daysToLive*24*60*60) +';path=/';
        document.cookie = cookie;
    }
}

function siteGetCookie(name) {
    // Split cookie string and get all individual name=value pairs in an array
    var cookieArr = document.cookie.split(";");
    // Loop through the array elements
    for(var i = 0; i < cookieArr.length; i++) {
        var cookiePair = cookieArr[i].split("=");
        // Removing whitespace at the beginning of the cookie name and compare it with the given string
        if(name == cookiePair[0].trim()) {
            // Decode the cookie value and return
            return decodeURIComponent(cookiePair[1]);
        }
    }
    // Return null if not found
    return null;
}

function encryptMessage(message, shift) {
    let result = '';
    for (let i = 0; i < message.length; i++) {
        let charCode = message.charCodeAt(i);
        result += String.fromCharCode(charCode + shift);
    }
    return result;
}

function decryptMessage(encryptedMessage, shift) {
    let result = '';
    for (let i = 0; i < encryptedMessage.length; i++) {
        let charCode = encryptedMessage.charCodeAt(i);
        result += String.fromCharCode(charCode - shift);
    }
    return result;
}
