library(igraph)
G<-as.undirected(read.graph("test.gml", format = "gml"))
#tkplot(G,layout=layout.fruchterman.reingold)

cent<-data.frame(bet=betweenness(G),eig=evcent(G)$vector)
# evcent returns lots of data associated with the EC, but we only need the
# leading eigenvector
res<-lm(eig~bet,data=cent)$residuals
cent<-transform(cent,res=res)
# We will use the residuals in the next step


library(ggplot2)
# We use ggplot2 to make things a
# bit prettier
p<-ggplot(cent,aes(x=bet,y=eig,colour=res,
    size=abs(res)))+xlab("Betweenness
    Centrality")+ylab("Eigenvector
    Centrality")
# We use the residuals to color and
# shape the points of our plot,
# making it easier to spot outliers.
p+geom_text()+opts(title="Key Actor Analysis for Political Donations")

 # Create positions for all of
 # the nodes w/ force directed
 l<-layout.fruchterman.reingold(G,
     niter=50)
 # Set the nodes’ size relative to
 # their residual value
 V(G)$size<-abs(res)*10
 # Only display the labels of key
 # players
 nodes<-as.vector(V(G)+1)
 # Key players defined as have a
 # residual value >.25
 nodes[which(abs(res)<.25)]<-NA
 # Save plot as PDF
 pdf("actor_plot.pdf",pointsize=7)
 plot(G,layout=l,vertex.label=nodes,
     vertex.label.dist=0.25,
     vertex.label.color="red",edge.width=1)
 dev.off()
  
  

      
   

