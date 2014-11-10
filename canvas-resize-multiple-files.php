<!DOCTYPE html> 
<html lang="en"> 
    <head>
        <meta charset="utf-8" />
        <title>canvasResize + zepto + multiple images</title>
        <meta name="description" content="Javascript Canvas Resize Plugin. It can work both with jQuery and Zepto. It's compatible with iOS6 and Android 2.3+. Modified for multiple images." />
        <meta name="keywords" content="javascript, jquery, zepto, canvas, image, resize" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link type="text/plain" rel="author" href="http://gokercebeci.com/humans.txt" />
        
		<link rel="stylesheet" href="style.css" type="text/css" media="screen" />
        
        <!-- zepto -->
        <script src="zepto.min.js"></script>
    </head>
    <body>
        <div id="devcontainer">
            <!-- development area -->

            <!-- SAMPLE -->
            <section>
                <h2>SAMPLE</h2>

                <div id="area">
                    <h3>canvasResize + zepto.js with multiple image upload</h3>
					<p>
					kgretk: added multiple image upload, performance stats logged.
					</p>
					
					<div id="kimages">
						<div class="ki">
							<div class="kimg"></div>
							<p><span></span></p>
						</div>
					</div>

                    <div id="kinput">
                        <input name="photo" type="file" multiple />
                        <u>Upload file(s)</u>
                        <p><span></span></p>
                        <i></i>
                    </div>
					
                    <script>
						// set options
						var rw = 1900; // resized on client size to 1900x1400
						var rh = 1400;
						var rq = 80; // quality
						var uploader = 'uploader4.php'; // ?s=< ? php echo uniqid(true);? >
						var perf_log = 1; // requires log.php
						
						
						$().ready(function() {

                            //$('#area u').click(function() {
                            //    $('input[name=photo]').trigger('click');
                            //});
							
							//start image
                            var si = 0;
                            var ef = {};
                            
                            $('input[name=photo]').change(function(e) {
								// RESET
								$('#area img, #area canvas').remove();
 
                                ef = e.target.files;
                                $(document).trigger('next');

                            });
                                
                            $(document).on('next', function(e2, next){
								var start, stop, stop2 = 0;
								
								if (si<ef.length) {
								  
									if (perf_log)
										start = window.performance.now();

									var file = ef[si];
									
									// prepare div for next image
									$('.ki').first().clone().appendTo('#kimages').attr('id','ki'+si);
									var ki = $('.ki').last();
									ki.css('display','block');
									var kispan = $('span', ki);
									var kimg = $('div', ki);
									
									kispan.css({
										'width': "5%"
									}).html("5%");
									
									si++;

									// CANVAS RESIZING

								    canvasResize(file, {
										width: rw,
										height: rh,
										crop: false,
										quality: rq,
										rotate: 0,
										callback: function(data, width, height, iw, ih) { //iw, ih added to get orig size
											
											if (perf_log)
												stop = window.performance.now();
										
											// SHOW AS AN IMAGE
											// =================================================
											var img = new Image();
											img.onload = function() {
												
												$(this).appendTo(kimg);

											};
											$(img).attr('src', data);

											// IMAGE UPLOADING
											// =================================================

											// Create a new formdata
											var fd = new FormData();
											// Add file data
											var f = canvasResize('dataURLtoBlob', data);
											f.name = file.name;
											fd.append($('#area input').attr('name'), f);

											var xhr = new XMLHttpRequest();
											xhr.open('POST', uploader, true);
											xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
											xhr.setRequestHeader("pragma", "no-cache");
											//Upload progress
											xhr.upload.addEventListener("progress", function(e) {
												if (e.lengthComputable) {
													var loaded = Math.ceil((e.loaded / e.total) * 100);
													kispan.css({
														'width': loaded + "%"
													}).html(loaded + "%");
												}
											}, false);
											// File uploaded
											xhr.addEventListener("load", function(e) {
												var response = JSON.parse(e.target.responseText);
												if (response.filename) {
													// Complete
													kispan.css({
														'width': '100%'
													}).html('OK');
													
													// log performance data
													if (perf_log) {
														stop2 = window.performance.now();
														save_perf(file, f, iw, ih, start, stop, stop2);
														}
													
													// next image
													$(document).trigger('next');
													
												}
											}, false);
											// Send data
											xhr.send(fd);

											// /IMAGE UPLOADING
											// =================================================              
										}
									}); //end canvasresize
								
								} // end if not last image
                            }); // end on next
                            
							function save_perf(file, f, iw, ih, start, stop, stop2) {
							
								// f1: uploaded image (file size, resolution)
								// f2: resized image, client size (file size)
								// dur: resizing duration
								// upl: uploading duration
								// speed: upload speed
								
								var info = 'f1,'+file.size+', '+iw+'x'+ih+', f2,'+f.size+ ', dur,' + (stop-start).toFixed(2) + 'ms, upl,' + (stop2-stop).toFixed(2) + 'ms' + ' ,speed,'+((f.size*8)/(stop2-stop)).toFixed(2)+' kbps';
								
								var xperf = new XMLHttpRequest();
								xperf.open('GET', 'log.php?i='+info, true);
								xperf.setRequestHeader("X-Requested-With", "XMLHttpRequest");
								xperf.setRequestHeader("pragma", "no-cache");
								xperf.send();
								
								console.log(file.name +', '+ info);
							}
                            
                        });
						
						// *********** performance.now()-polyfill.js from https://gist.github.com/paulirish/5438650
						
						// relies on Date.now() which has been supported everywhere modern for years.
						// as Safari 6 doesn't have support for NavigationTiming, we use a Date.now() timestamp for relative values

						// if you want values similar to what you'd get with real perf.now, place this towards the head of the page
						// but in reality, you're just getting the delta between now() calls, so it's not terribly important where it's placed

						(function(){

						  // prepare base perf object
						  if (typeof window.performance === 'undefined') {
							  window.performance = {};
						  }

						  if (!window.performance.now){
							
							var nowOffset = Date.now();

							if (performance.timing && performance.timing.navigationStart){
							  nowOffset = performance.timing.navigationStart
							}


							window.performance.now = function now(){
							  return Date.now() - nowOffset;
							}

						  }

						})();
                    </script>

                </div>
				                <!-- description -->
                <div class="desc">
                    <h1>zepto canvasResize v1.2.0</h1>
                    <hr>
                    <p><b>canvasResize</b> is a plugin for client side image resizing.</p>
                    <p>It's compatible with iOS6 It can work 
                        both with jQuery and Zepto</p>
                    <p>I fixed iOS6 Safari's image file rendering issue for large size image (over mega-pixel)
                        using few functions from 
                        <a href="https://github.com/stomita/ios-imagefile-megapixel">ios-imagefile-megapixel</a><br/>
                        And fixed orientation issue by using 
                        <a href="https://github.com/jseidelin/exif-js">exif-js</a></p>
                    <p><b>* Sorry, server side uploading option  does not work on gokercebeci.com. You can test it on your own server.</b></p>
                    <p>I've only tested it on</p>
                    <ul>
                        <li><b>Chromium (24.0.1312.56)</b>, </li>
                        <li><b>Google Chrome (25.0.1364.68 beta)</b>, </li>
                        <li><b>Opera (12.14)</b>, </li>
                        <li><b>IOS 6.1.2</b></li>
                    </ul>
                    <p>and it works enough for me for now!</p>
                    <p>It is under MIT License and It requires "binaryajax.js" and "exif.js" to work which is also under the MPL License [http://www.nihilogic.dk/licenses/mpl-license.txt]</p>
                    <p class="signature">
						<a href="http://gokercebeci.com/dev/" title="developer">goker.cebeci, the developer</a>
					</p>
                    <p class="signature">
						<a href="https://github.com/gokercebeci/canvasResize">fork it on github</a>
					</p>
                </div>
                <!-- /description -->
                <div class="clearfix"></div>
				
				<script src="binaryajax.js"></script>
				<script src="exif.js"></script>
				<script src="canvasResize.js"></script>
            </section>
            <!-- SAMPLE -->


            <!-- /development area -->
        </div>
    </body>
</html> 
