const CryptoJS = require("crypto-js");
console.log(CryptoJS.AES.decrypt(process.argv[2], "gefdzfdef").toString(CryptoJS.enc.Utf8))