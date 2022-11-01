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
	pop.pTitle.style.cursor = "pointer";
    pop.pText = document.getElementById("poptext");
	pop.pText.style.cursor = "pointer";
  },

  // (B) OPEN POPUP
  open : (title, text) => {
	var message_show = "Text_" + text;
	var title_show = "Goal_" + text;
	var mtext = window[message_show];
	var mtitle = window[title_show];

    pop.pTitle.innerHTML = mtitle;
    pop.pText.innerHTML = mtext;
    pop.pWrap.classList.add("open");
  },

  // (C) CLOSE POPUP
  close : () => { pop.pWrap.classList.remove("open"); }
};
	
window.addEventListener("DOMContentLoaded", pop.init);

var detail = {
	/* initialize variables */
	detail_msg: null, 
	dtext: null,
	dtitle: null, 
	wrapper: null,
	
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
		detail.dtitle = document.getElementById("detailtitle");
	},
	 
	open : (x, w_z) => {

		detail_msg = "Detail_" + x + "_" + w_z;
		detailtext = window[detail_msg];
		
		detail_msgtitle = "DetailTitle_" + x + "_" + w_z;
		detailtitle = window[detail_msgtitle];

		detail.dtitle.innerHTML = detailtitle;
		detail.dtext.innerHTML = detailtext;
		detail.wrapper.classList.add("open");
		
	},
	close: () => { detail.wrapper.classList.remove("open"); }
};
window.addEventListener("DOMContentLoaded", detail.init);
