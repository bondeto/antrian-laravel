from PIL import Image

img = Image.open("app_icon.png")
icon_sizes = [(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)]
img.save("app_icon.ico", sizes=icon_sizes)
print("Icon created: app_icon.ico")
