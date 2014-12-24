#!/usr/local/bin/python

import sys, os, pystache, urllib, pprint

wp_codes = ['bg','ba','dk','de','ca','gb','au','es','pe','cl','ir','fr','es','il','hu','id','it','kr','mm','no','nl','pl','pt','br','ru','sk','rs','se','tr','cn','tw','us']
# contries covered by wp localizations (reonstructed from WP-Langcodes without countrycode)
# langcode	Country
#	'ar'	'eg','dz','bh','dj','er','iq','il','ye','jo','qa','km','kw','lb','ly','ma','mr','om','sa','so','sd','sy','td','tn','ae',
#	'az'	'az',
#	'ca'	'fr',
#	'cy'	'gb',
#	'eu'	'es',
#	'fi'	'fi',
#	'gd'	'gb',
#	'hr'	'hr',
#	'ja'	'jp',
#	'th'	'th',
# known languages having more than one country
#	'de'	'ch','at','be'
#	

do_codes = []

css_template = """

.i18n-item {
	position: relative !important;
	width: 1.3333333333333333em !important;
	height:1em !important;
	margin-right:0.5em !important;
}

.i18n-item[data-language],
.i18n-item[data-country] {
	position: relative;
	display: inline-block;
	width: 1.3333333333333333em;
	height:1em;
	line-height: 1em;
	background-size: contain;
	background-position: 50%;
	background-repeat: no-repeat;
}
.i18n-item[data-language] {
	border:1px solid rgba(30,30,30,0.8);
	background-color:rgba(255,255,255,0.9);
}
.i18n-item[data-language][data-country] {
	border-style:none;
	background-color:transparent;
}

.i18n-item[data-language]:after {
	position:absolute;
	right:0;
	left:0;
	bottom:0;
	content:attr(data-language);
	text-align:center;
	text-transform:uppercase;
	font-size: 0.8em;
	line-height:1.2em;
	font-weight:bold;
	color:#000;
	background-color:transparent;
}
.i18n-item[data-language][data-country]:after {
	left:auto;
	padding:0 0.1em;
	font-size: 0.6em;
	background:rgba(255,255,255,0.9);
}

.i18n-item[data-language].invert:after {
	color:#fff;
	background:rgba(0,0,0,0.6);
}


{{#country_codes}}
.i18n-item[data-country='{{country_code_lower}}'],
.i18n-item[data-country='{{country_code_upper}}'] {
	background-image:url( '../flags/4x3/{{country_code_lower}}.svg' );
}
{{/country_codes}}
"""
#	background-image:url( 'data:image/svg+xml;base64,{{flag_data}}' );


flag_path = os.path.dirname(os.path.realpath(__file__))+'/flags/4x3/'
out_path = os.path.dirname(os.path.realpath(__file__))+'/css/l18n.css'
template_data = {
	'country_codes':[]
}

for entry in os.listdir(flag_path):
	entry_path = os.path.join(flag_path,entry)
	country_code = os.path.splitext(entry)[0]
	if not len(do_codes) or country_code in do_codes:
		svg_code = open(entry_path,'rb').read()
		template_data['country_codes'].append({
			'country_code_lower':country_code.lower(),
			'country_code_upper':country_code.upper(),
			'flag_data':urllib.quote( svg_code.encode('base64') )
		})

content = pystache.render( css_template, template_data)
f = open(out_path,'w')
f.write(content)
f.close()

#print repr(template_data)
	
