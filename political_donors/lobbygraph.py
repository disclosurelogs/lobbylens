import networkx as nx
import xmlrpclib, time
#import matplotlib.pyplot as plt
from xml.dom.minidom import parse
G=nx.Graph()
dom = parse("political_donors.xml")
for node in dom.getElementsByTagName('node'):
	nodeID = G.add_node(node.attributes["id"].value)
	if "weight" in node.attributes.keys():
		G.node[node.attributes["id"].value]['weight'] = node.attributes["weight"].value
	if "graphic_fill_color" in node.attributes.keys():
		G.node[node.attributes["id"].value]['color'] = node.attributes["graphic_fill_color"].value

for edge in dom.getElementsByTagName('edge'):
	if "weight" in edge.attributes.keys():
		G.add_edge(edge.attributes["tail_node_id"].value, edge.attributes["head_node_id"].value, weight=edge.attributes["weight"].value)
	else:
		G.add_edge(edge.attributes["tail_node_id"].value, edge.attributes["head_node_id"].value)

#nx.write_pajek(G,"test.pj")

#nx.draw(G)
#plt.show()
#     <node id="donationrecipient-Australian Labor Party (ACT Branch)" label="Donation Recipient: Australian Labor Party (ACT Branch)" 
#weight="0.048587647924891" shape="circle"
# label_bg_line_color="#0000FF" graphic_fill_color="#0000FF" graphic_line_color="#0000FF"/>

server = xmlrpclib.Server('http://127.0.0.1:20738/RPC2')
ubi_server = server.ubigraph
ubi_server.clear()
edges={}    # Dictionary to record UbiGraph edge ids
newIDs = {}
# Add nodes
for n in G.nodes(data=True):
    oldid = str(n[0])
    # newid = int("".join ( ['%d'%ord(b) for b in oldid] ))
    newid = ubi_server.new_vertex()
    newIDs[oldid] = newid
    ubi_server.set_vertex_attribute(newid, "size", n[1].get('weight','0.5'))
    ubi_server.set_vertex_attribute(newid, "color", n[1].get('color','32'))
# Add edges
for e in G.edges(data=True):
    e_id=ubi_server.new_edge(newIDs[e[0]],newIDs[e[1]])
    ubi_server.set_edge_attribute(e_id, "strength", e[2].get('weight','0.5'))
    #ubi_server.set_edge_attribute(newid, "color", oldid)
    time.sleep(0.0125)
