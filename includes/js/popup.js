/* first popup */
var pop = {
  // (A) ATTACH POPUP HTML
  pWrap : null,  // html popup wrapper
  pTitle : null, // html popup title
  pText : null,  // html popup text
  init : () => {
    // (A1) POPUP WRAPPER
    pop.pWrap = document.createElement("div");
    pop.pWrap.id = "popwrap";
    document.body.appendChild(pop.pWrap);

    // (A2) POPUP INNERHTML - only return if header is clicked
    pop.pWrap.innerHTML =
    `<div id="popbox" >
      <h1 id="poptitle" onclick="pop.close()" ></h1>
	  <p id="poptext"></p>
      </div>`;
    pop.pTitle = document.getElementById("poptitle");
	pop.pTitle.style.cursor = "default";
    pop.pText = document.getElementById("poptext");
	pop.pText.style.cursor = "pointer";
  },

  // (B) OPEN POPUP - change
  open : (title, text) => {
	var message_show = "Text_" + text;
	var title_show = "Goal_" + text;
	var mtext = window[message_show];
	var mtitle = window[title_show];

	pop.pTitle.innerHTML = '<table style="width:100%"><tr><td>' + mtitle + '</td><td style="text-align:right">&#10006;</td></tr></table>';
    pop.pText.innerHTML = mtext;
    pop.pWrap.classList.add("open");
  },

 // (B) OPEN POPUP - change
  sprint : (title, text) => {
	var message_show= "CurDetail_" + title + "_" + text;
	var title_show  = "CurDetailTitle_" + title + "_" + text;

	var mtext = window[message_show];
	var mtitle = window[title_show];

	window_height = window.innerHeight;
	window_width = window.innerWidth;
	pop.pText.style.maxHeight = window_height * .7 + "px"; /* "400px"; */
	pop.pText.style.maxWidth = window_width * .6 + "px"; /* "400px"; */
	pop.pTitle.innerHTML = '<table style="width:100%"><tr><td>' + mtitle + '</td><td style="text-align:right">&#10006;</td></tr></table>';
    pop.pText.innerHTML = mtext;
    pop.pWrap.classList.add("open");
  },

  // (C) CLOSE POPUP
  close : () => { pop.pWrap.classList.remove("open"); }
};
	
window.addEventListener("DOMContentLoaded", pop.init);

/* This is used for the second popup */
var detail = {
	/* initialize variables */
	detail_msg: null, 
	dtext: null,
	dtitle: null, 
	wrapper: null,
	window_height: null,
	
	init : () => {
		detail.wrapper = document.createElement("div");
		detail.wrapper.id = "detailwrap";
		document.body.appendChild(detail.wrapper);
		
		detail.wrapper.innerHTML =
			`<div id="detailbox" onclick="detail.close()">
				<H1 id="detailtitle"></H1>
				<p id="detailtext"></p>
			</div>`;	
		detail.dtext = document.getElementById("detailtext");
		detail.dtext.style.cursor = "default";
		detail.dtitle = document.getElementById("detailtitle");
		detail.dtitle.style.cursor = "default";
	},
	 
	open : (x, w_z) => {

		detail_msg = "Detail_" + x + "_" + w_z;
		detailtext = window[detail_msg];
		
		detail_msgtitle = "DetailTitle_" + x + "_" + w_z;
		detailtitle = window[detail_msgtitle];
		
		window_height = window.innerHeight;
		window_width = window.innerWidth;
		detail.dtext.style.maxHeight = window_height * .7 + "px"; /* "400px"; */
		detail.dtext.style.maxWidth = window_width * .6 + "px"; /* "400px"; */
		detail.dtitle.innerHTML = '<table style="width:100%"><tr><td>' + detailtitle + '</td><td style="text-align:right">&#10006;</td></tr></table>'; 
		detail.dtext.innerHTML = detailtext;
		detail.wrapper.classList.add("open");
		
	},
	close: () => { detail.wrapper.classList.remove("open"); }
};
window.addEventListener("DOMContentLoaded", detail.init);

/* This is used for the current sprint drill down list */
var curspr = {
  // (A) ATTACH POPUP HTML
  cWrap : null,  // html popup wrapper
  cTitle : null, // html popup title
  cText : null,  // html popup text
  window_height: null,
	
  init : () => {
    // (A1) POPUP WRAPPER
    curspr.cWrap = document.createElement("div");
    curspr.cWrap.id = "popwrap";
    document.body.appendChild(curspr.cWrap);

    // (A2) POPUP INNERHTML - only return if header is clicked
    curspr.cWrap.innerHTML =
    `<div id="popbox" onclick="curspr.close()" >
      <h1 id="poptitle" >hello world</h1>
	  <p id="poptext">The quick brown fox jumps over the lazy dog</p>
      </div>`;
	  
    curspr.cTitle = document.getElementById("poptitle");
	curspr.cTitle.style.cursor = "default";
    curspr.cText = document.getElementById("poptext");
	curspr.cText.style.cursor = "pointer";
  },

  // (B) OPEN POPUP - change
  open : (x, w_z)  => {
	detail_msg = "CurDetail_" + x + "_" + w_z;
	detailtext = window[detail_msg];
		
	detail_msgtitle = "CurDetailTitle_" + x + "_" + w_z;
	detailtitle = window[detail_msgtitle];


	curspr.cText.innerHTML = detailtext ; 
	curspr.cTitle.innerHTML = detailtitle;
	
	alert(curspr.cText.innerHTML);
	curspr.cWrap.classList.add("open");
  },

  // (C) CLOSE POPUP
  close : () => { curspr.cWrap.classList.remove("open"); }
};
	
window.addEventListener("DOMContentLoaded", curspr.init);