# GET EXTENSION

Use modman [Linux](https://github.com/colinmollenhour/modman) | [Windows](https://github.com/khoazero123/modman-php) :

	cd magento_root/
    modman clone https://github.com/devfgct/M2_extra_promotion.git
	modman M2_extra_promotion

Use git:

    git clone https://github.com/devfgct/M2_extra_promotion.git
    mv M2_extra_promotion/* magento_root/


# INSTALLATION

	bin/magento setup:upgrade
	bin/magento setup:di:compile
