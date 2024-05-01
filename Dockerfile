# Use an official Node.js runtime as the base image
FROM node:10
RUN npm install
COPY . .
EXPOSE 3306
CMD ["npm", "start"]
