$(document).ready(function() {
soblur();
sequ();
hotwen();
abautor();
hotping();
function soblur(){
			var zt='';
			$.ajax({				
                //url: globals.ajax_url,
                url: SPZ.BASE_site+"/sp/soblur",
				type: "GET",
                async: true,                          
				dataType: "json",
				success: function(data) {
				    
				if(data.zthtml) { zt = '<li><span class="vhiy"><i class="cv icon iconfont icon-icon_time"></i>专题：</span></li><div class="so_list"><div class="s_tag">'+data.zthtml+'</div></div>';  }   
				$(".top_so").append(data.sohtml+zt);
				
				$(".menu_zt").append(data.zthtml);
				
				},
				error: function(err) {
					console.log(err)
				}
			}); 
  
}

function sequ(){
        
            $.ajax({				
                //url: globals.ajax_url,
                url: SPZ.BASE_site+"/sp/sequ",
				type: "GET",
                async: true,                          
				dataType: "json",
				success: function(data) {
				$(".sequ").append(data.html);
				},
				error: function(err) {
				console.log(err)
				}
			}); 
}


function hotwen(){
        
            $.ajax({				
                //url: globals.ajax_url,
                url: SPZ.BASE_site+"/sp/hotwen",
				type: "GET",
                async: true,                          
				dataType: "json",
				success: function(data) {
				$(".hot-wen").append(data.hotinfo);
				},
				error: function(err) {
					console.log(err)
				}
			}); 
}

function hotping(){
            
            $.ajax({				
                //url: globals.ajax_url,
                url: SPZ.BASE_site+"/sp/hotping",
				type: "GET",
                async: true,                          
				dataType: "json",
				success: function(data) {
				$(".hot-ping").append(data.hotping);
				},
				error: function(err) {
					console.log(err)
				}
			}); 
}

function abautor(){
			var uid = $(".abautor").data("uid");
			$.ajax({			
     
                url: SPZ.BASE_site+"/sp/abautor",
				type: "POST",  
				data: {
				    uid:uid
				},
                async: true,                          
				dataType: "json",
				success: function(data) {
				  
				  $(".abautor").append(data.info);
				 
				},
				error: function(err) {
		          console.log(err);
				}
	        }); 
}

$(".rec-btn").on("click", function() {
    // 在这里添加你想要执行的代码
    $(".rec-btn").html('正在生成...');
    var textContent = $(".entry-content").text();
    var cid = $(".rec-btn").data("cid");
    var currentUrl = window.location.href;
            $.ajax({			
     
                url: SPZ.BASE_site+"/Deng/comtion",
				type: "POST",  
				data: {
				    cid:cid,
				    textContent:textContent,
				    currentUrl:currentUrl
				},
                async: true,                          
				dataType: "json",
				success: function(data) {
				cocoMessage.success('生成完毕');
				$(".rec-btn").html('智能回复');
				console.log(data.content);
				},
				error: function(err) {
		          console.log(err);
		          $(".rec-btn").html('智能回复');
				}
	        }); 
    
});


});