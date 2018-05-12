import selenium
from selenium.webdriver import Firefox
from selenium.webdriver.firefox.options import Options
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import time
from os import listdir, path, getcwd, makedirs
import random
import sys
from subprocess import call

text_file_path = getcwd() + '/tags.txt'
text_file = open(text_file_path, "r")
tags = text_file.read().split(' ')

#from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
#cap = DesiredCapabilities().FIREFOX
#cap["marionette"] = False

selenium.webdriver.DesiredCapabilities.FIREFOX["unexpectedAlertBehaviour"] = "accept"

opts = Options()
#opts.set_headless()
#assert opts.headless # operating in headless mode
browser = Firefox(options=opts)
browser.get('https://www.xvideos.com/account/uploads/new')

browser.find_element_by_id("signin-form_login").send_keys("mitya@mail.ubgvpn.xyz")
browser.find_element_by_id("signin-form_password").send_keys("entrezXVsvp")
#browser.find_element_by_class_name(".btn .btn-danger .btn-lg .has-verror").click()
browser.find_element_by_xpath("//button[contains(.,'Log in')]").click()		

try:
	alert = browser.switch_to.alert
	alert.accept()
except NoAlertPresentException:
	pass
	

videos_path = sys.argv[1]

water_videos_path = videos_path + '/water/'
if not path.exists(water_videos_path):
	makedirs(water_videos_path)
ffmpeg_path = sys.argv[2]
watermark = getcwd() + '/watermark.png'
			
for f in listdir(videos_path):
	if path.isfile(videos_path + f):

		time.sleep(2) 
		browser.find_element_by_xpath('//*[@id="upload_form_category_category_centered_category_straight"]').click()

		time.sleep(2) 
		browser.find_element_by_xpath('//*[@id="upload_form_networksites_networksites_centered_networksites_DEFAULT_ONLY"]').click()

		# Watermarked video creation
		input_name = videos_path + f
		output_name = water_videos_path + f
		call([ffmpeg_path, "-i", input_name, "-i", watermark, "-filter_complex", "[1:v][0:v]scale2ref=iw*0.0004*ih*2.45:ih*0.0004*iw[logo1][base];[base][logo1]overlay=(main_w-overlay_w):(main_h-overlay_h)", "-vcodec", "libx264", output_name])

		filename, file_extension = path.splitext(f)

		random_tags_for_title = random.sample(tags, 2)
		#browser.find_element_by_xpath('//*[@id="upload_form_titledesc_title"]').send_keys(filename)
		browser.find_element_by_xpath('//*[@id="upload_form_titledesc_title"]').send_keys(" ".join(random_tags_for_title))

		list_of_random_tags = random.sample(tags, 4)
		for tag in list_of_random_tags:
			#browser.find_element_by_xpath('/html/body/div/div[4]/div/div/div[2]/div/div[2]/form/fieldset[6]/div/div/div/div[1]/button').click()
			browser.find_element_by_xpath('//*[@class="add"]').click()
			browser.find_element_by_xpath('//*[@class="focus"]').send_keys(tag)
		browser.find_element_by_xpath('//*[@class="add"]').click()
		
		
		browser.find_element_by_xpath('//*[@class="checkbox-error-box"]').click()

		browser.find_element_by_xpath('//input[@id="upload_form_file_file_options_file_1_file"]').send_keys(videos_path + '\\water\\' + f)
		time.sleep(2) 
		browser.find_element_by_xpath('//button[contains(.,"Upload")]').click()

	
		WebDriverWait(browser, 100).until(EC.presence_of_element_located((By.XPATH, '//*[@class="status text-success" and text()="Upload completed successfully! The file is being checked for publication..."]')))
				
		browser.get('https://www.xvideos.com/account/uploads/new')