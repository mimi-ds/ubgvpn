import selenium
from selenium.webdriver import Firefox
from selenium.webdriver.firefox.options import Options
from selenium.common.exceptions import NoAlertPresentException
import time
from os import listdir, path
import random
import os

text_file_path = os.getcwd() + '\\tags.txt'
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

browser.find_element_by_id("signin-form_login").send_keys("vasek1234567@shitmail.me")
browser.find_element_by_id("signin-form_password").send_keys("vasyadigital1234")
#browser.find_element_by_class_name(".btn .btn-danger .btn-lg .has-verror").click()

browser.find_element_by_xpath("//button[contains(.,'Log in')]").click()

try:
	alert = browser.switch_to.alert
	alert.accept()
except NoAlertPresentException:
	pass

time.sleep(2) 
browser.find_element_by_xpath('//*[@id="upload_form_category_category_centered_category_straight"]').click()

time.sleep(2) 
browser.find_element_by_xpath('//*[@id="upload_form_networksites_networksites_centered_networksites_DEFAULT_ONLY"]').click()

videos_path = os.getcwd() + '\\videos\\'
for f in listdir(videos_path):
	filename, file_extension = path.splitext(f)
	browser.find_element_by_xpath('//*[@id="upload_form_titledesc_title"]').send_keys(filename)
	list_of_random_tags = random.sample(tags, 10)
print list_of_random_tags