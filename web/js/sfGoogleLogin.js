function googleLogin() {
	
	this.popupWindow = null;
	this.popupWatch = null;

	this.getWindowInnerSize = function() {
		var width = 0;
		var height = 0;
		var elem = null;
		if ('innerWidth' in window) {
			// For non-IE
			width = window.innerWidth;
			height = window.innerHeight;
		} else {
			// For IE
			if (('BackCompat' === window.document.compatMode)
					&& ('body' in window.document)) {
				elem = window.document.body;
			} else if ('documentElement' in window.document) {
				elem = window.document.documentElement;
			}
			if (elem !== null) {
				width = elem.offsetWidth;
				height = elem.offsetHeight;
			}
		}
		return [ width, height ];
	}

	this.getParentCoords = function() {
		var width = 0;
		var height = 0;
		if ('screenLeft' in window) {
			// IE-compatible variants
			width = window.screenLeft;
			height = window.screenTop;
		} else if ('screenX' in window) {
			// Firefox-compatible
			width = window.screenX;
			height = window.screenY;
		}
		return [ width, height ];
	}

	this.googlePopupClose = function() {
		if (!googleLogin.popupWindow || googleLogin.popupWindow.closed) {
			googleLogin.popupWindow = null;
			window.clearInterval(googleLogin.popupWatch);
			location.reload();
		}
	}

	this.googleLoginPopup = function() {
		var parentSize = this.getWindowInnerSize();
		var parentPos = this.getParentCoords();
		var xPos = parentPos[0]
				+ Math.max(0, Math.floor((parentSize[0] - 500) / 2));
		var yPos = parentPos[1]
				+ Math.max(0, Math.floor((parentSize[1] - 500) / 2));
		var url = $('a.googleLogin').attr('href');
		googleLogin.popupWindow = window.open(url, "",
				"width=500,height=500,status=1,location=1,resizable=yes,left="
						+ xPos + ",top=" + yPos);
		googleLogin.popupWatch = window.setInterval( this.googlePopupClose , 80);
	}
}