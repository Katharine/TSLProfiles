Event.observe(window,'load',function() {
	$$('img.imgthumb').each(function(item) {
		Event.observe(item,'click',function(event) {
			new Effect.Fade($('gallerywin').setStyle({position: 'absolute', width: '728px'}));
			new Effect.Appear('detailwin');
		});
	});
});