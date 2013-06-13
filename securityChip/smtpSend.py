import serial, sys, feedparser, time
import smtplib 

#reception coordonnees
i=4
a=""
msg=""
while (a==""):
	while(i>0):
		ser = serial.Serial("/dev/tty.usbmodemfd121", 9600) 
		a=ser.readline()
		print a 
		msg+=a
		i=i-1
ser.close()

#envoi alerte
src = "protoprojet@gmail.com" 
password = "*****" 
dest = "projetinnov4@gmail.com" 


mail = "To: " + dest + "\nFrom: " + src + "\nSubject: Alerte Security Chip\n\n" + "Votre enfant est en danger:\n\n"+msg+"\nSecurity Chip"

smtp = smtplib.SMTP('smtp.gmail.com',587) 
smtp.set_debuglevel(1) 
smtp.ehlo() 
smtp.starttls() 
smtp.ehlo() 
smtp.login(src, password) 
smtp.sendmail(src, dest, mail) 
smtp.close() 