---------------------------------------------
CUSTOM SIDEBAR PLUGIN HOW TO:
---------------------------------------------

1. Install and activate the plugin
2. Add the code below outside your loop.
3. You're good to go!!


---------------------------------------------
THE CODE:
---------------------------------------------

<?php
	$imgs = get_the_images(true, true);
	var_dump($imgs);
?>


---------------------------------------------
NOTE:
---------------------------------------------

get_the_images(true, true);

The first parameter is full size image the second is thumbnails.
If you only want thumbnails, simply write get_the_images(false, true)
and vice versa.