import networkx as nx
import xmlrpclib, time
#import matplotlib.pyplot as plt
from xml.dom.minidom import parse
G=nx.Graph()
dom = parse("political_donors.xml")
for node in dom.getElementsByTagName('node'):
	nodeID = G.add_node(node.attributes["id"].value)
        if "label" in node.attributes.keys():
		G.node[node.attributes["id"].value]['label'] = node.attributes["label"].value
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
nx.write_gexf(G,"test.gexf")
#nx.draw(G)
#plt.show()

server = xmlrpclib.Server('http://192.168.117.138:20738/RPC2')
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
