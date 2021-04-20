/**
 * PhantomJS action file used to tell Phantom how to capture screens. The
 * timeout is used to give JS a change to change content on the page before
 * taking the screen shot.
 */
var page = require('webpage').create();
var system = require('system');
var address, output, size, selector, host, session;

// Get the input parameters.
address = system.args[1];
output = system.args[2];
session = system.args[3];
host = system.args[4];
selector = system.args[5];

// Require file system
var fs = require('fs');

// If the session is not set to none, then create the cookie
if (session != 'none') {

    // Read the session value information
    file = '../private_tmp/' + session
    try {
        f = fs.open(file, "r");
        value = f.read();
        f.close();
    } catch (e) {
        console.log(e);
    }
    value = value.trim();

    // Add Cookie
    phantom.addCookie({
        'name'  :  session,
        'value'  :  value,
        'domain'  :  host,
        'path'  :  '/',
        'httponly'  :  true,
        'secure'  :  true,
        'expires' : (new Date()).getTime() + (5000 * 60 * 60)
    });

}


//DEBUGGING
//fs.write('testlogfile_phantomjs.txt', address + '\n' + output + '\n' + session + '\n' + host + '\n-' + selector + '-\n');

// Set the capture size to full HD.
page.viewportSize = { width: 1240, height: 1754 };

// Check if PDF should be rendered.
if (system.args.length === 5 && system.args[1].substr(-4) === ".pdf") {
  size = system.args[2].split('*');
  page.paperSize = size.length === 2 ? { width: size[0], height: size[1], margin: '0px' } : { format: system.args[2], orientation: 'portrait', margin: '1cm' };
}

// Open the address and capture the screen after 200 mill-seconds (give the
// screen the chance to run JS alters).
page.open(address, function (status) {
  if (status !== 'success') {
    // Error fetching page.
    console.log('500');
    phantom.exit();
  } else {
    window.setTimeout(function () {
      // Page fetch and timeout ran out... so capture the screen.
      if (selector) {
        var clipRect = page.evaluate(function (selector) {
          var domelement = document.querySelector(selector);

          if (domelement) {
            return domelement.getBoundingClientRect();
          } else {
            return undefined;
          }
        }, selector);

        if (clipRect) {
          page.clipRect = {
              top:    clipRect.top,
              left:   clipRect.left,
              width:  clipRect.width,
              height: clipRect.height
          };
        }
      }

      page.render(output);
      console.log('200');
      phantom.exit();
    }, 200);
  }
});
