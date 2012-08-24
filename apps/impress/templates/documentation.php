

<div id="documentation">

<h1>Impress Documentation</h1>
<br />


<h2>What is Impress?</h2>
Impress is an ownCloud application that can play presentation based on the amazing <a href="http://bartaz.github.com/impress.js">impress.js</a> library.
<br /><br />

<h2>How do I use it?</h2>
You have to define your presentation by writing a HTML file manually. But there are great examples and it isn´t that difficult.
Just put a HTML file with the file extension .impress into your ownCloud and it will show up here in the Impress app automatically.
If you click on it it opens up in a new windows.
<br /><br />

<h2>How do I define the presentation?</h2>
The best way to learn it is to look at the example presenation. We suggest that you copy it and place it into your ownCloud with the name demo.impress and play around with it. You can edit in ownCloud directly with the internal text editor and play it here in the Impress app.
<br />

<textarea class="examplecode">
   <!--
   		Each step of the presentation should be an element inside the `#impress` with a class name
   		of `step`. These step elements are positioned, rotated and scaled by impress.js, and
   		the 'camera' shows them on each step of the presentation.
   
   		Positioning information is passed through data attributes.
   
   		In the example below we only specify x and y position of the step element with `data-x="-1000"`
   		and `data-y="-1500` attributes. This means that **the center** of the element (yes, the center)
   		will be positioned in point x = -1000px and y = -1500px of the presentation 'canvas'.
   
   		It will not be rotated or scaled.
   
   	-->
   	<div id="bored" class="step slide active present" data-x="-1000" data-y="-1500" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(-1000px, -1500px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(1); -webkit-transform-style: preserve-3d; ">
   		<q>Aren't you just <b>bored</b> with all those slides-based presentations?</q>
   	</div>
   
   	<!--
   
   		The `id` attribute of the step element is used to identify it in the URL, but it's optional.
   		If it is not defined, it will get a default value of `step-N` where N is a number of slide.
   
   		So in the example below it'll be `step-2`.
   
   		The hash part of the url when this step is active will be `#/step-2`.
   
   		You can also use `#step-2` in a link, to point directly to this particular step.
   
   		Please note, that while `#/step-2` (with slash) would also work in a link it's not recommended.
   		Using classic `id`-based links like `#step-2` makes these links usable also in fallback mode.
   
   	-->
   	<div class="step slide future" data-x="0" data-y="-1500" id="step-2" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(0px, -1500px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(1); -webkit-transform-style: preserve-3d; ">
   		<q>Don't you think that presentations given <strong>in modern browsers</strong> shouldn't <strong>copy the limits</strong> of 'classic' slide decks?</q>
   	</div>
   
   	<div class="step slide future" data-x="1000" data-y="-1500" id="step-3" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(1000px, -1500px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(1); -webkit-transform-style: preserve-3d; ">
   		<q>Would you like to <strong>impress your audience</strong> with <strong>stunning visualization</strong> of your talk?</q>
   	</div>
   
   	<!--
   
   		This is an example of step element being scaled.
   
   		Again, we use a `data-` attribute, this time it's `data-scale="4"`, so it means that this
   		element will be 4 times larger than the others.
   		From presentation and transitions point of view it means, that it will have to be scaled
   		down (4 times) to make it back to it's correct size.
   
   	-->
   	<div id="title" class="step future" data-x="0" data-y="0" data-scale="4" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(0px, 0px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(4); -webkit-transform-style: preserve-3d; ">
   		<span class="try">then you should try</span>
   		<h1>impress.js<sup>*</sup></h1>
   		<span class="footnote"><sup>*</sup> no rhyme intended</span>
   	</div>
   
   	<!--
   
   		This element introduces rotation.
   
   		Notation shouldn't be a surprise. We use `data-rotate="90"` attribute, meaning that this
   		element should be rotated by 90 degrees clockwise.
   
   	-->
   	<div id="its" class="step future" data-x="850" data-y="3000" data-rotate="90" data-scale="5" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(850px, 3000px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(90deg) scale(5); -webkit-transform-style: preserve-3d; ">
   		<p>It's a <strong>presentation tool</strong> <br>
   		inspired by the idea behind <a href="http://prezi.com/">prezi.com</a> <br>
   		and based on the <strong>power of CSS3 transforms and transitions</strong> in modern browsers.</p>
   	</div>
   
   	<div id="big" class="step future" data-x="3500" data-y="2100" data-rotate="180" data-scale="6" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(3500px, 2100px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(180deg) scale(6); -webkit-transform-style: preserve-3d; ">
   		<p>visualize your <b>big</b> <span class="thoughts">thoughts</span></p>
   	</div>
   
   	<!--
   
   		And now it gets really exiting! We move into third dimension!
   
   		Along with `data-x` and `data-y`, you can define the position on third (Z) axis, with
   		`data-z`. In the example below we use `data-z="-3000"` meaning that element should be
   		positioned far away from us (by 3000px).
   
   	-->
   	<div id="tiny" class="step future" data-x="2825" data-y="2325" data-z="-3000" data-rotate="300" data-scale="1" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(2825px, 2325px, -3000px) rotateX(0deg) rotateY(0deg) rotateZ(300deg) scale(1); -webkit-transform-style: preserve-3d; ">
   		<p>and <b>tiny</b> ideas</p>
   	</div>
   
   	<!--
   
   		This step here doesn't introduce anything new when it comes to data attributes, but you
   		should notice in the demo that some words of this text are being animated.
   		It's a very basic CSS transition that is applied to the elements when this step element is
   		reached.
   
   		At the very beginning of the presentation all step elements are given the class of `future`.
   		It means that they haven't been visited yet.
   
   		When the presentation moves to given step `future` is changed to `present` class name.
   		That's how animation on this step works - text moves when the step has `present` class.
   
   		Finally when the step is left the `present` class is removed from the element and `past`
   		class is added.
   
   		So basically every step element has one of three classes: `future`, `present` and `past`.
   		Only one current step has the `present` class.
   
   	-->
   	<div id="ing" class="step future" data-x="3500" data-y="-850" data-rotate="270" data-scale="6" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(3500px, -850px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(270deg) scale(6); -webkit-transform-style: preserve-3d; ">
   		<p>by <b class="positioning">positioning</b>, <b class="rotating">rotating</b> and <b class="scaling">scaling</b> them on an infinite canvas</p>
   	</div>
   
   	<div id="imagination" class="step future" data-x="6700" data-y="-300" data-scale="6" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(6700px, -300px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(6); -webkit-transform-style: preserve-3d; ">
   		<p>the only <b>limit</b> is your <b class="imagination">imagination</b></p>
   	</div>
   
   	<div id="source" class="step future" data-x="6300" data-y="2000" data-rotate="20" data-scale="4" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(6300px, 2000px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(20deg) scale(4); -webkit-transform-style: preserve-3d; ">
   		<p>want to know more?</p>
   		<q><a href="http://github.com/bartaz/impress.js">use the source</a>, Luke!</q>
   	</div>
   
   	<div id="one-more-thing" class="step future" data-x="6000" data-y="4000" data-scale="2" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(6000px, 4000px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(2); -webkit-transform-style: preserve-3d; ">
   		<p>one more thing...</p>
   	</div>
   
   	<!--
   
   		And the last one shows full power and flexibility of impress.js.
   
   		You can not only position element in 3D, but also rotate it around any axis.
   		So this one here will get rotated by -40 degrees (40 degrees anticlockwise) around X axis and
   		10 degrees (clockwise) around Y axis.
   
   		You can of course rotate it around Z axis with `data-rotate-z` - it has exactly the same effect
   		as `data-rotate` (these two are basically aliases).
   
   	-->
   	<div id="its-in-3d" class="step future" data-x="6200" data-y="4300" data-z="-100" data-rotate-x="-40" data-rotate-y="10" data-scale="2" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(6200px, 4300px, -100px) rotateX(-40deg) rotateY(10deg) rotateZ(0deg) scale(2); -webkit-transform-style: preserve-3d; ">
   		<p><span class="have">have</span> <span class="you">you</span> <span class="noticed">noticed</span> <span class="its">it's</span> <span class="in">in</span> <b>3D<sup>*</sup></b>?</p>
   		<span class="footnote">* beat that, prezi ;)</span>
   	</div>
   
   	<!--
   
   		So to make a summary of all the possible attributes used to position presentation steps, we have:
   
   		* `data-x`, `data-y`, `data-z` - they define the position of **the center** of step element on
   			the canvas in pixels; their default value is 0;
   		* `data-rotate-x`, `data-rotate-y`, 'data-rotate-z`, `data-rotate` - they define the rotation of
   			the element around given axis in degrees; their default value is 0; `data-rotate` and `data-rotate-z`
   			are exactly the same;
   		* `data-scale` - defines the scale of step element; default value is 1
   
   		These values are used by impress.js in CSS transformation functions, so for more information consult
   		CSS transfrom docs: https://developer.mozilla.org/en/CSS/transform
   
   	-->
   	<div id="overview" class="step future" data-x="3000" data-y="1500" data-scale="10" style="position: absolute; -webkit-transform: translate(-50%, -50%) translate3d(3000px, 1500px, 0px) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(10); -webkit-transform-style: preserve-3d; ">
   	</div>
   
   </div></div>
   
   <!--
   
   	Hint is not related to impress.js in any way.
   
   	But it can show you how to use impress.js features in creative way.
   
   	When the presentation step is shown (selected) its element gets the class of "active" and the body element
   	gets the class based on active step id `impress-on-ID` (where ID is the step's id)... It may not be
   	so clear because of all these "ids" in previous sentence, so for example when the first step (the one with
   	the id of `bored`) is active, body element gets a class of `impress-on-bored`.
   
   	This class is used by this hint below. Check CSS file to see how it's shown with delayed CSS animation when
   	the first step of presentation is visible for a couple of seconds.
   
   	...
   
   	And when it comes to this piece of JavaScript below ... kids, don't do this at home ;)
   	It's just a quick and dirty workaround to get different hint text for touch devices.
   	In a real world it should be at least placed in separate JS file ... and the touch content should be
   	probably just hidden somewhere in HTML - not hard-coded in the script.
   
   	Just sayin' ;)
   
   -->	
</textarea>	


<br /><br />



<h2>Credits</h2>
Many thanks to Bartek Szopka for creating the wonderful impress.js library and also the demo presentation who made this app possible.
<br /><br />



</div>