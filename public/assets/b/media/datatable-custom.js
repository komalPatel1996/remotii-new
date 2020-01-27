// JavaScript Document
jQuery(document).ready(function(){	

	   
	   var id = -1;//simulation of id
				$('#example').dataTable({ bJQueryUI: true,

							"sPaginationType": "full_numbers"
}).makeEditable({
									sUpdateURL: function(value, settings)
									{
                             							return(value); //Simulation of server-side response using a callback function
									},
                             			sAddURL: "AddData.php",
                             			sAddHttpMethod: "GET",
                                    sDeleteHttpMethod: "GET",
									sDeleteURL: "DeleteData.php",
                    							"aoColumns": [
                    									{ 	cssclass: "required" },
                    									{
                    									},
                    									{
                									        indicator: 'Saving platforms...',
                                                            					tooltip: 'Click to edit platforms',
												type: 'text',
                                                 						//submit:'Save changes'
                                                                        onblur: 'submit',
                    									},
                    									{
                                                            					indicator: 'Saving Engine Version...',
                                                            					tooltip: 'Click to select engine version',
                                                            					loadtext: 'loading...',
                           					                                type: 'text',
                               						            		onblur: 'submit',
												//submit: 'Ok',
                                                            					//loadurl: 'EngineVersionList.php',
												//loadtype: 'GET'
                    									},
                    									{
                                                            					indicator: 'Saving CSS Grade...',
                                                            					tooltip: 'Click to select CSS Grade',
                                                            					loadtext: 'loading...',
                           					                                type: 'select',
                               						            		onblur: 'submit',
                                                            					data: "{'':'Please select...', 'A':'A','B':'B','C':'C'}"
                                                        				},
                                                        {
                                                                                indicator: 'Saving new1...',
                                                                                tooltip: 'Click to select new1',
                                                                                
                                                                            type: 'text',
                                                                        onblur: 'submit',
                                                                              
                                                                        },
                                                                        {}
											],
									oAddNewRowButtonOptions: {	label: "Add...",
													icons: {primary:'ui-icon-plus'} 
									},
									oDeleteRowButtonOptions: {	label: "Remove", 
													icons: {primary:'ui-icon-trash'}
									},

									oAddNewRowFormOptions: { 	
                                                    title: 'Add a new browser',
													show: "blind",
													hide: "explode",
                                                    modal: true
									}	,
sAddDeleteToolbarSelector: ".dataTables_length"								

										});
				
	   
   });