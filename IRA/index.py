from flask import Flask, render_template, request, jsonify
import time
from TextClassification import naive_bayes_classification
app = Flask(__name__)
@app.route("/")
def index():
     return render_template("text_classifier.html")
@app.route("/text_classifier", methods=["GET", "POST"])
def text_classifier_method():
    if request.method == "POST":
        text = request.form.get("text")

        start_time = time.time()
        results = naive_bayes_classification(text)

        end_time = time.time()
        execution_time = end_time - start_time
        execution_time_formatted = "{:.2f}".format(execution_time)

        return render_template("text_classifier.html", text=text, results=results, time = execution_time_formatted)

    return render_template("text_classifier.html")


if __name__ == "__main__":
    app.run(port=8888)
