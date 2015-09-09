#!/bin/bash
wav2png='/home/emoura/wav2png/bin/Linux/wav2png'
for file in ./sounds/*.wav
do 
	fout=$(echo $file | sed 's/wav/png/' | sed 's/sounds/waves/')
	echo "$file > $fout"
	`$wav2png --foreground-color=000000ff --background-color=ffffffff $file -o $fout`
done