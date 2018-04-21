import selenium
from selenium.webdriver import Firefox
from selenium.webdriver.firefox.options import Options
import time

#from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
#cap = DesiredCapabilities().FIREFOX
#cap["marionette"] = False

selenium.webdriver.DesiredCapabilities.FIREFOX["unexpectedAlertBehaviour"] = "accept"

opts = Options()
#opts.set_headless()
#assert opts.headless # operating in headless mode
browser = Firefox(options=opts)
browser.get('https://www.xvideos.com/account/uploads/new')

#browser.find_element_by_id("search_form_input_homepage").send_keys("search query")
#browser.find_element_by_id("search_button_homepage").click()

browser.find_element_by_id("signin-form_login").send_keys("vasek1234567@shitmail.me")
browser.find_element_by_id("signin-form_password").send_keys("vasyadigital1234")
#browser.find_element_by_class_name(".btn .btn-danger .btn-lg .has-verror").click()

browser.find_element_by_xpath("//button[contains(.,'Log in')]").click()

alert = browser.switch_to.alert
alert.accept()

time.sleep(10) 
browser.find_element_by_xpath('//*[@id="upload_form_category_category_centered_category_straight"]').click()

