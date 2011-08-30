#!/usr/bin/python
import MySQLdb
import math
from genshi.template import TemplateLoader

DB_HOST = "localhost"
DB_USER = "team7"
DB_PASS = ""
DB_NAME = "team7"

__db = MySQLdb.connect(host=DB_HOST ,user=DB_USER ,passwd=DB_PASS,db=DB_NAME, charset = "utf8", use_unicode = True)
__db.autocommit(True)
cur = __db.cursor()

query = "select t1.company_name, t2.lat, t2.lon from companies as t1 inner join postcodes as t2 on t1.postcode = t2.postcode"
cur.execute(query)
loc_list = cur.fetchall()
cur.close()

def yield_info():
    for i in loc_list:
        #lon =  float(i[2]) * 20037508.34 / 180
        #lat = math.log(math.tan((90 + float(i[1])) * math.pi / 360)) / (math.pi / 180)
        #lat = lat * 20037508.34 / 180;
        #coords = '%s, %s' % (lon, lat)
        coords = '%s, %s' % (i[2], i[1])
        name = i[0]
        color = '000000'
        yield {
            'name': name,
            'coordinates': coords,
            'color': color,
            }

if __name__ == "__main__":
    loader = TemplateLoader(['.'])
    template = loader.load('template-lobby.kml')
    stream = template.generate(collection=yield_info())
    filename = 'lobby.kml'
    f = open(filename, 'w')
    f.write(stream.render())
    f.close()

