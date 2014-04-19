#!bin/bash

test_1() {
ab -n 100 -c 1 -g out.dat http://local.sigma.dev/
ab -n 100 -c 10 -g out2.dat http://local.sigma.dev/
ab -n 100 -c 25 -g out3.dat http://local.sigma.dev/
gnuplot plot.p
}

test_1
