window.onload = function() {
	function disableselect(e){
	return false;
	};
	
	function reEnable(){
	return true;
	};
	document.onmousedown=disableselect;
	document.onclick=reEnable;
};