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

    // (A2) POPUP INNERHTML
    pop.pWrap.innerHTML =
    `<div id="popbox" onclick="pop.close()">
      <h1 id="poptitle" ></h1>
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
