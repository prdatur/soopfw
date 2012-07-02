SoopfwPager = function(o) {
	this._options = {
		post_variable: '',
		container: '',
		effect: 'replace',
		current_page: 0,
		pages: 0,
		entries: 0,
		max_entries_per_page: 0,
		range: 10,
		front_range: 1,
		end_range: 0,
		link_template: 0,
		is_ajax: false,
		uuid: ""
	};

	this.containers = {

	};
	$.extend(this._options, o);

	$.extend(SoopfwPager.prototype, {
		build_pager: function(page) {

			//Return empty pager couse we do not reached our max entries per page
			if(this._options.entries <= this._options.max_entries_per_page) {
				$("#pager_"+this._options.uuid).html("");
				return;
			}
			var pagerHTML = $("#pager_"+this._options.uuid);
			pagerHTML.html("");

			//Get our needed data
			var current_page = 0;
			if(page != undefined) {
				current_page = page;
			}
			else {
				current_page = this._options.current_page;
			}
			var entries = this._options.entries;
			var max_entries_per_page = this._options.max_entries_per_page;
			var range = this._options.range;
			var front_range = this._options.front_range;
			var end_range = this._options.end_range;

			//Calculate the pages
			var pages = Math.ceil(entries/max_entries_per_page);

			//Setup next and prev page index
			var next_page = current_page;
			var prev_page = current_page;
			next_page++;
			prev_page--;

			if(next_page >= pages) { //Next page is more than we have pages so set it to first page
				next_page = 0;
			}

			if(prev_page < 0) { //prev page is smaller than 0 so set it to the max page
				prev_page = pages-1;
			}

			var range_from = Math.floor(current_page-(range/2));
			if(range_from < 0) {
				range_from = 0;
			}

			var range_to = range_from+range;
			if(range_to > pages) {
				range_to = pages;
				range_from = range_to-range;
			}

			if(range_from < 0) {
				range_from = 0;
			}

			//Build up previous page
			if(prev_page != pages-1) {

				var first_container = $("<span class='pager_pagelinks pager_first'></span>");
				this.containers['first_container'] = first_container;
				first_container.append(this.get_page_link(0, false, Soopfw.t("First")));
				pagerHTML.append(first_container);

				var previous_container = $("<span class='pager_pagelinks pager_previous'></span>");
				this.containers['previous_container'] = previous_container;
				previous_container.append(this.get_page_link(prev_page, false, Soopfw.t("Previous")));
				pagerHTML.append(previous_container);
			}

			//Build up the front range
			var front_container = $("<span class='pager_pagelinks pager_front_range'></span>");
			this.containers['front_container'] = front_container;
			var front_range_start = (range_from > front_range) ? front_range : range_from;
			if(0 < front_range_start) {
				pagerHTML.append(front_container);
			}
			for(var i = 0;i < front_range_start;i++) {
				front_container.append(this.get_page_link(i));
			}

			if(0 < front_range_start) {
				front_container.append(" ... ");
			}

			//Build up middle range
			var middle_container = $("<span class='pager_pagelinks pager_middle_range'></span>");
			this.containers['middle_container'] = middle_container;
			if(range_from < range_to) {
				pagerHTML.append(middle_container);
			}
			for(i = range_from;i < range_to;i++) {
				middle_container.append(this.get_page_link(i, (i==current_page)));
			}

			//Build up the end range
			var end_container = $("<span class='pager_pagelinks pager_end_range'> ... </span>");
			this.containers['end_container'] = end_container;
			var end_range_start = pages-end_range;
			if(end_range_start <= range_to) {
				end_range_start = range_to;
			}

			if(end_range_start < pages) {
				pagerHTML.append(end_container);
			}

			for(i = end_range_start;i < pages;i++) {
				end_container.append(this.get_page_link(i));
			}

			//Build up next page
			if(next_page != 0) {

				var next_container = $("<span class='pager_pagelinks pager_next'></span>");
				this.containers['next_container'] = next_container;
				next_container.append(this.get_page_link(next_page, false, Soopfw.t("Next")));
				pagerHTML.append(next_container);

				var last_container = $("<span class='pager_pagelinks pager_last'></span>");
				this.containers['last_container'] = last_container;
				next_container.append(this.get_page_link(pages-1, false, Soopfw.t("Last")));
				pagerHTML.append(last_container);
			}

			//pagerHTML.append("<div class=\"clean\"></div>");

		},
		get_page_link: function(page, selected, text) {
			if(text == undefined || text == null) {
				text = page+1;
				if(text < 10) {
					text = "0"+text;
				}
			}

			if(this._options.is_ajax == false) {
				if(selected == true) {
					return "<b>"+text+"</b>";
				}

				return "<a  href=\""+str_replace("%page%",page,this._options.link_template)+"\">"+text+"</a>";
			}
			else {
				var css_class = "";
				if(selected == true) {
					css_class = " page_link_selected"
				}
				var link = $("<a href=\"javascript:void(0);\" page=\""+page+"\" class=\""+css_class+"\">"+text+"</a>");
				var that = this;
				link.click(function() {
					var page = $(this).attr("page");
					var post_data = {};
					if(that._options.post_variable != undefined && that._options.post_variable != "") {
						post_data[that._options.post_variable] = page;
					}

					$(that.containers['middle_container']).find("a").removeClass("page_link_selected");
					$(that.containers['middle_container']).find('a[page="'+page+'"]').addClass("page_link_selected");
					$.ajax({
						url: str_replace("%page%", page, that._options.link_template),
						data: post_data,
						dataType:'json',
						success: function(result) {
							parse_ajax_result(result, function(html_replacement) {
								if(that._options.effect == "fade") {
									$(that._options.container).hide();
									$(that._options.container).html(html_replacement);
									$(that._options.container).fadeIn("slow");
								}
								else {
									$(that._options.container).html(html_replacement);
								}
								that.build_pager(page);
							});
						}
					});
				});
			}
			return link[0];

		}
	});
};

//var x = new SoopfwPager({});
//Handle pager ajax requests
Soopfw.behaviors.system_pager_ajax = function() {
	if(Soopfw.config.system_pager_ajax != undefined) {
		for(i = 0; i < Soopfw.config.system_pager_ajax.length; i++) {
			var row = Soopfw.config.system_pager_ajax[i];
			var pager = new SoopfwPager(row);
			pager.build_pager();
		}
	}
};