# output as png image
set terminal png

# save file to "out.png"
set output "out.png"

# graph title
set title "Sigma Benchmarking"

# nicer aspect ratio for image size
set size 1,0.7

# y-axis grid
set grid y

# x-axis label
set xlabel "request"

# y-axis label
set ylabel "response time (ms)"

#plot data from "out.dat" using column 9 with smooth sbezier lines
# and title of "nodejs" for the given data
plot "out.dat" using 9 smooth sbezier with lines title "out1", \
"out2.dat" using 9 smooth sbezier with lines title "out2", \
"out3.dat" using 9 smooth sbezier with lines title "out3"
