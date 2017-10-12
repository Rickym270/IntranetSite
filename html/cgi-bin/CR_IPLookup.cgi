#!/usr/local/bin/python2.7
'''
  Author:   Ricky Martinez
  Purpose:  Provides a tool to search up what the IP is registered for. The ability to switch
            between regions is available. The ability to accept wildcards on FULL OCTETS ONLY.
            Ability to create/view IP Registration Request.

            Green Check:  The item has been pushed
            Check:        The item is in queue to be pushed

            The information is pulled from the Network_Group, Network_Object and 
            Network_Group_to_Object
'''

import jinja2
import cgi
import cgitb
import MySQLdb
import datetime
import collections
import pprint

cgitb.enable();
templates = jinja2.Environment(loader = jinja2.FileSystemLoader(searchpath="templates"))

######################
debug_list = []
######################

#GET IP Address entered
form = cgi.FieldStorage()
ip_address = form.getfirst("IPSearch", '').strip()
region = form.getfirst("Region","US")

#GET DB infor
with open('/var/www/html/tythonmysql.ini','r') as f:
  lines = f.readlines()
  for i, line in enumerate(lines):
    if i == 0:
      host = line[:-1]
    elif i == 1:
      username = line[:-1]
    elif i == 2:
      password = line[:-1]
    elif i == 3:
      socket = line[:-1]


db = MySQLdb.connect(host=host, user=username, passwd = password, db = "BlockedIPChecker", unix_socket=socket)
cur = db.cursor()

######OPEN Corresponding template
filename = 'ServiceName_'+region+'.txt'
with open(filename,'r') as q:
  serv_lines = q.readlines()
  del serv_lines[0]

#ORDERED Dictionary is created to preserve order of items
service_dictionary = collections.OrderedDict() 

colspan_list = []

#PARSES through ServiceNames.txt to get all necesarry info
for value in serv_lines:
  service_name = value.strip().split(',')[:1][0]
  group_type = value.strip().split(',')[1:2][0]
  group_name = value.strip().split(',')[2:3][0]
  service_group = value.strip().split(',')[3:4][0]
  if not service_group in service_dictionary:
    service_dictionary[service_group]=collections.OrderedDict()
  if not service_name in service_dictionary[service_group]:
    service_dictionary[service_group][service_name]=[]
  service_dictionary[service_group][service_name].append(group_type)
  ##SERVICE name is next to corresponding column
  service_dictionary[service_group][service_name].append(group_name)

##GET COLSPAN
prev_group = ''
for group in service_dictionary:
  debug_list.append(group);
  group_length = 0
  if prev_group != group:
    for service in service_dictionary[group]:
      group_length += len(service_dictionary[group][service])/2
      prev_group = group
    debug_list.append(group_length)
    colspan_list.append(group_length)

## WILDCARD search
isWildcard = False
comparison_operator = ['<','>']
new_ip_start = []
new_ip_end = []
octet_start = '0'
octet_end = '255'
res_count = 0
res2_count = 0

if '*' in ip_address:
  wild_ip = ip_address.split('.')
  for octet in wild_ip:
    if not octet:
      octet = 'None'
    new_ip_start.append(octet)
    new_ip_end.append(octet)
    if octet == '*':
      new_ip_start.pop()
      new_ip_end.pop()
      new_ip_start.append(octet_start)
      new_ip_end.append(octet_end)

      isWildcard = True
      comparison_operator = ['>','<']

  if len(wild_ip) != 4:
    missing_oct = 4-len(wild_ip)
    for i in range(0,missing_oct):
      new_ip_start.append(octet_start)
      new_ip_end.append(octet_end)
  new_ip_start = '.'.join(new_ip_start)
  new_ip_end = '.'.join(new_ip_end)
else:
  new_ip_start = ip_address
  new_ip_end = ip_address

#If there is a search
if ip_address:
  result = {}
  
  #CHECK if the database is being rebuilt
  rebuild_query = "SELECT count(1) from Network_Object"
  cur.execute(rebuild_query)
  re_res = int(cur.fetchone()[0]);
   
  if not "data" in result:
    result["data"] = []

  if re_res != 0:
    query = "SELECT O.name, O.type, INET_NTOA(O.ip) AS 'First IP', INET_NTOA(O.last_ip) AS 'Last IP', INET_NTOA(O.mask) AS 'Mask', G.name AS 'Group' FROM Network_Obj O LEFT OUTER JOIN Network_Group_to_Member OG ON O.id = OG.Member_id LEFT OUTER JOIN Network_Group G ON G.id = OG.Group_id WHERE O.ip" + comparison_operator[0] + "= INET_ATON('"+new_ip_start+"') AND O.last_ip"+comparison_operator[1]+"= INET_ATON('"+ new_ip_end +"') AND O.ip != 0" 
    query_count=cur.execute(query)
    res = cur.fetchall();
    if query_count >0:
      res_count =1
    else:
      res_count = 0

        
    query2 = "SELECT INET_NTOA(ip),INET_NTOA(last_ip), service_group,name from Latest_Pushed_Data WHERE ip"+comparison_operator[0]+"= INET_ATON('"+new_ip_start+"') AND last_ip" +comparison_operator[1]+"= INET_ATON('"+new_ip_end+"') AND ip != 0"
    query2_count = cur.execute(query2)
    res2 = cur.fetchall();
    if query2_count >0:
      res2_count = 1
    else:
      res2_count = 0

    prev_name = ''
    prev_group = ''
    service_identifiers = {}
    for data in res:
      name = str(data[0]).strip()
      typeOf = str(data[1]).strip()
      firstIP = data[2].strip()
      lastIP = data[3].strip()
      mask = data[4]
      group = str(data[5]).strip()
       
      if not name in service_identifiers:
        service_identifiers[name] = []
      if name == prev_name:
        service_identifiers[name].append(group)
      else:
        service_identifiers[name].append(group)
                
      result["data"].append({
        "Prev_Name" :   prev_name,
        "Name"      :   name,
        "Type"      :   typeOf,
        "FirstIP"   :   firstIP,
        "LastIP"    :   lastIP,
        "Mask"      :   mask,
        "Group"     :   group,
      })
      prev_name = name
          
    result_length = len(result["data"])
    for i in range(0,result_length):
      if any(result["data"][i]["Name"] == data3[3] for data3 in res2):
        result["data"][i]["ActivePolicy"]="Active"
      else:
        result["data"][i]["ActivePolicy"]="NotActive"
####################################################################################################
  else:
    result={}
    prev_name=''
    service_identifiers=[]
    result_length= 0
    re_res=0
    res_count = 0
else:
  result = {} 
  prev_name = ''
  service_identifiers = []
  result_length= None
  re_res=None
  res_count = 0



template = templates.get_template("CR_IPSearch.html")
print(template.render(result=result,ip_address=ip_address,region=region,service_dictionary=service_dictionary,service_identifiers=service_identifiers, colspan_list=colspan_list,re_res=re_res,result_length=result_length,res2_count=res2_count,res_count=res_count,new_ip_start=new_ip_start,new_ip_end=new_ip_end, debug=debug_list)) 
